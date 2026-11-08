<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MensajePedido;
use App\Models\Pedido;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PedidoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->validate(['estado' => ['nullable', Rule::in(['pendiente', 'preparando', 'listo', 'entregado', 'cancelado'])]]);
        $pedidos = Pedido::with(['mesa', 'mesero', 'detalles.adiciones', 'mensajes'])->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            ->whereNotIn('estado', ['entregado', 'cancelado'])->latest()->get();
        return response()->json(['data' => $pedidos->map(fn ($p) => [
            'id_pedido' => $p->id_pedido, 'codigo_seguimiento' => $p->codigo_seguimiento, 'tipo_pedido' => $p->tipo_pedido,
            'mesa' => $p->mesa?->numero, 'cliente_nombre' => $p->cliente_nombre, 'cliente_telefono' => $p->cliente_telefono,
            'estado' => $p->estado, 'tiempo_estimado_minutos' => $p->tiempo_estimado_minutos, 'estimado_listo_en' => $p->estimado_listo_en,
            'mesero' => $p->mesero ? ['id_usuario' => $p->mesero->id_usuario, 'nombres' => $p->mesero->nombres] : null,
            'total' => (float) $p->total, 'propina' => (float) $p->propina,
            'items' => $p->detalles->map(fn ($d) => ['nombre' => $d->nombre_producto, 'cantidad' => $d->cantidad, 'nota' => $d->nota, 'subtotal' => (float) $d->subtotal,
                'adiciones' => $d->adiciones->map(fn ($a) => $a->nombre)]),
            'mensajes' => $p->mensajes->map(fn ($m) => ['id_mensaje' => $m->id_mensaje, 'autor' => $m->autor, 'mensaje' => $m->mensaje,
                'visibilidad' => $m->visibilidad, 'estado_publicacion' => $m->estado_publicacion, 'created_at' => $m->created_at]),
        ])]);
    }

    public function estado(Request $request, Pedido $pedido): JsonResponse
    {
        $data = $request->validate(['estado' => ['required', Rule::in(['pendiente', 'preparando', 'listo', 'entregado', 'cancelado'])], 'tiempo_estimado_minutos' => 'nullable|integer|min:1|max:240']);
        $pedido->estado = $data['estado'];
        if (isset($data['tiempo_estimado_minutos'])) {
            $pedido->tiempo_estimado_minutos = $data['tiempo_estimado_minutos'];
            $pedido->estimado_listo_en = now()->addMinutes($data['tiempo_estimado_minutos']);
        }
        $pedido->save();
        return response()->json(['message' => 'Estado del pedido actualizado.', 'data' => ['estado' => $pedido->estado, 'estimado_listo_en' => $pedido->estimado_listo_en]]);
    }

    public function asignarMesero(Request $request, Pedido $pedido): JsonResponse
    {
        $mesero = $request->user();
        if (! $mesero->estaActivo() || ! $mesero->tienePermiso('tomar_pedidos')) return response()->json(['message' => 'El usuario debe estar activo y tener permiso para tomar pedidos.'], 422);
        $pedido->update(['id_usuario' => $mesero->id_usuario]);
        return response()->json(['message' => 'Mesero asignado.', 'data' => ['nombres' => $mesero->nombres]]);
    }

    public function responder(Request $request, Pedido $pedido): JsonResponse
    {
        $data = $request->validate(['mensaje' => 'required|string|min:2|max:1000']);
        $mensaje = MensajePedido::create(['id_pedido' => $pedido->id_pedido, 'id_usuario' => $request->user()->id_usuario,
            'autor' => 'personal', 'clasificacion' => 'recomendacion', 'visibilidad' => 'privado', 'estado_publicacion' => 'no_aplica', 'mensaje' => $data['mensaje']]);
        return response()->json(['data' => ['id_mensaje' => $mensaje->id_mensaje, 'mensaje' => $mensaje->mensaje]] , 201);
    }

    public function moderarMensaje(Request $request, MensajePedido $mensaje): JsonResponse
    {
        $data = $request->validate(['estado_publicacion' => ['required', Rule::in(['publicado', 'oculto'])]]);
        if ($mensaje->visibilidad !== 'publico') return response()->json(['message' => 'Solo se moderan recomendaciones públicas.'], 422);
        $mensaje->update(['estado_publicacion' => $data['estado_publicacion']]);
        return response()->json(['message' => 'Recomendación moderada.']);
    }
}
