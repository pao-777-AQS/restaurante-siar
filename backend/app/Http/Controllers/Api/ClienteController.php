<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdicionProducto;
use App\Models\DetallePedido;
use App\Models\DetallePedidoAdicion;
use App\Models\Mesa;
use App\Models\MensajePedido;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Promocion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClienteController extends Controller
{
    public function menu(): JsonResponse
    {
        $productos = Producto::with(['categoria', 'adiciones' => fn ($q) => $q->where('activa', true)])
            ->enCarta()->where('disponible', true)->orderBy('nombre')->get();
        $promos = Promocion::with('productos:id_producto')->where('activa', true)
            ->where('dia_semana', now()->isoWeekday())->where(fn ($q) => $q->whereNull('fecha_inicio')->orWhereDate('fecha_inicio', '<=', today()))
            ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', today()))->get();
        $descuentos = [];
        foreach ($promos as $promo) foreach ($promo->productos as $producto) {
            $descuentos[$producto->id_producto] = max($descuentos[$producto->id_producto] ?? 0, min(15, (float) $promo->descuento_porcentaje));
        }
        $recomendaciones = MensajePedido::where('visibilidad', 'publico')->where('estado_publicacion', 'publicado')->latest()->limit(8)->get(['mensaje', 'created_at']);
        return response()->json(['data' => $productos->map(fn ($p) => [
            'id_producto' => $p->id_producto, 'nombre' => $p->nombre, 'descripcion' => $p->descripcion,
            'precio' => (float) $p->precio, 'imagen_url' => \App\Services\ProductoService::urlImagen($p->imagen_url),
            'categoria' => $p->categoria?->nombre, 'descuento_porcentaje' => $descuentos[$p->id_producto] ?? 0,
            'adiciones' => $p->adiciones->map(fn ($a) => ['id_adicion' => $a->id_adicion, 'nombre' => $a->nombre, 'precio' => (float) $a->precio]),
        ]), 'recomendaciones' => $recomendaciones]);
    }

    public function crearPedido(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tipo_pedido' => 'required|in:mesa,recoger', 'codigo_qr' => 'required_if:tipo_pedido,mesa|nullable|uuid',
            'cliente_nombre' => 'required|string|max:160', 'cliente_telefono' => 'required|string|max:40',
            'propina' => 'nullable|numeric|min:0|max:1000000', 'items' => 'required|array|min:1|max:30',
            'items.*.id_producto' => 'required|integer|distinct', 'items.*.cantidad' => 'required|integer|min:1|max:30',
            'items.*.nota' => 'nullable|string|max:400', 'items.*.adiciones' => 'array', 'items.*.adiciones.*' => 'integer',
        ]);
        $mesa = null;
        if ($data['tipo_pedido'] === 'mesa') {
            $mesa = Mesa::where('codigo_qr', $data['codigo_qr'])->where('activo', true)->first();
            if (! $mesa || $mesa->estado->value === 'mantenimiento') throw ValidationException::withMessages(['codigo_qr' => 'El QR no corresponde a una mesa disponible.']);
        }
        $items = []; $subtotal = 0; $descuentoTotal = 0;
        foreach ($data['items'] as $item) {
            $producto = Producto::with(['categoria', 'adiciones'])->find($item['id_producto']);
            if (! $producto || ! $producto->estaEnMenu() || ! $producto->disponible) throw ValidationException::withMessages(['items' => 'Uno de los productos ya no está disponible. Actualiza la carta.']);
            $adiciones = $producto->adiciones->where('activa', true)->whereIn('id_adicion', $item['adiciones'] ?? []);
            if ($adiciones->count() !== count(array_unique($item['adiciones'] ?? []))) throw ValidationException::withMessages(['items' => 'Una adición elegida no está disponible para este producto.']);
            $promo = Promocion::where('activa', true)->where('dia_semana', now()->isoWeekday())
                ->where(fn ($q) => $q->whereNull('fecha_inicio')->orWhereDate('fecha_inicio', '<=', today()))
                ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', today()))
                ->whereHas('productos', fn ($q) => $q->where('productos.id_producto', $producto->id_producto))
                ->orderByDesc('descuento_porcentaje')->first();
            $pct = min(15, (float) ($promo?->descuento_porcentaje ?? 0));
            $base = (float) $producto->precio + (float) $adiciones->sum('precio');
            $unitDiscount = round($base * $pct / 100, 2); $lineSubtotal = round($base * $item['cantidad'], 2); $lineDiscount = round($unitDiscount * $item['cantidad'], 2);
            $subtotal += $lineSubtotal; $descuentoTotal += $lineDiscount;
            $items[] = compact('producto', 'adiciones', 'item', 'base', 'pct', 'lineSubtotal', 'lineDiscount');
        }
        $tip = (float) ($data['propina'] ?? 0);
        $pedido = DB::transaction(function () use ($data, $mesa, $items, $subtotal, $descuentoTotal, $tip) {
            if ($mesa) {
                $mesa = Mesa::whereKey($mesa->id_mesa)->lockForUpdate()->firstOrFail();
                if ($mesa->estado->value === 'mantenimiento') throw ValidationException::withMessages(['codigo_qr' => 'La mesa está fuera de servicio.']);
                if ($mesa->estado->value !== 'ocupada') $mesa->ocupar();
            }
            $pedido = Pedido::create([
                'codigo_seguimiento' => (string) Str::uuid(), 'tipo_pedido' => $data['tipo_pedido'],
                'id_mesa' => $mesa?->id_mesa, 'cliente_nombre' => $data['cliente_nombre'], 'cliente_telefono' => $data['cliente_telefono'],
                'estado' => 'pendiente', 'tiempo_estimado_minutos' => 30, 'estimado_listo_en' => now()->addMinutes(30),
                'subtotal' => $subtotal, 'descuento' => $descuentoTotal, 'propina' => $tip,
                'total' => $subtotal - $descuentoTotal + $tip, 'estado_pago' => 'pendiente',
            ]);
            foreach ($items as $line) {
                $detalle = DetallePedido::create([
                    'id_pedido' => $pedido->id_pedido, 'id_producto' => $line['producto']->id_producto,
                    'nombre_producto' => $line['producto']->nombre, 'precio_unitario' => $line['base'],
                    'cantidad' => $line['item']['cantidad'], 'nota' => $line['item']['nota'] ?? null,
                    'subtotal' => $line['lineSubtotal'], 'descuento_porcentaje' => $line['pct'], 'descuento' => $line['lineDiscount'],
                ]);
                foreach ($line['adiciones'] as $add) DetallePedidoAdicion::create(['id_detalle_pedido' => $detalle->id_detalle_pedido, 'id_adicion' => $add->id_adicion, 'nombre' => $add->nombre, 'precio' => $add->precio]);
            }
            return $pedido;
        });
        return response()->json(['data' => $this->pedidoPublico($pedido->load(['detalles.adiciones', 'mesa']))], 201);
    }

    public function seguimiento(string $codigo): JsonResponse
    {
        $pedido = Pedido::with(['detalles.adiciones', 'mesa', 'mesero'])->where('codigo_seguimiento', $codigo)->firstOrFail();
        return response()->json(['data' => $this->pedidoPublico($pedido)]);
    }

    public function comentar(Request $request, string $codigo): JsonResponse
    {
        $data = $request->validate(['mensaje' => 'required|string|min:2|max:1000', 'visibilidad' => 'required|in:privado,publico']);
        $pedido = Pedido::where('codigo_seguimiento', $codigo)->firstOrFail();
        $msg = MensajePedido::create(['id_pedido' => $pedido->id_pedido, 'autor' => 'cliente', 'clasificacion' => 'recomendacion',
            'visibilidad' => $data['visibilidad'], 'estado_publicacion' => $data['visibilidad'] === 'publico' ? 'pendiente' : 'no_aplica', 'mensaje' => $data['mensaje']]);
        return response()->json(['data' => ['id_mensaje' => $msg->id_mensaje, 'mensaje' => $msg->mensaje, 'visibilidad' => $msg->visibilidad, 'estado' => $msg->estado_publicacion]], 201);
    }

    private function pedidoPublico(Pedido $pedido): array
    {
        $publicos = MensajePedido::where('id_pedido', $pedido->id_pedido)->where('visibilidad', 'publico')->where('estado_publicacion', 'publicado')->get(['mensaje', 'created_at']);
        return ['codigo_seguimiento' => $pedido->codigo_seguimiento, 'tipo_pedido' => $pedido->tipo_pedido, 'mesa' => $pedido->mesa?->numero,
            'estado' => $pedido->estado, 'tiempo_estimado_minutos' => $pedido->tiempo_estimado_minutos, 'estimado_listo_en' => $pedido->estimado_listo_en,
            'mesero' => $pedido->mesero?->nombres, 'subtotal' => (float) $pedido->subtotal, 'descuento' => (float) $pedido->descuento,
            'propina' => (float) $pedido->propina, 'total' => (float) $pedido->total,
            'items' => $pedido->detalles->map(fn ($d) => ['nombre' => $d->nombre_producto, 'cantidad' => $d->cantidad, 'subtotal' => (float) $d->subtotal,
                'adiciones' => $d->adiciones->map(fn ($a) => ['nombre' => $a->nombre, 'precio' => (float) $a->precio])]),
            'mensajes' => $pedido->mensajes()->where(fn ($q) => $q->where('visibilidad', 'privado')->orWhere(fn ($pub) => $pub->where('visibilidad', 'publico')->where('estado_publicacion', 'publicado')))->get(['autor', 'visibilidad', 'mensaje', 'created_at']),
            'recomendaciones_publicas' => $publicos];
    }
}
