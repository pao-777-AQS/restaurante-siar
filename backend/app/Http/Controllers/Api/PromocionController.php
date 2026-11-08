<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdicionProducto;
use App\Models\Producto;
use App\Models\Promocion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromocionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['productos' => Producto::with('adiciones')->where('activo', true)->orderBy('nombre')->get()->map(fn ($p) => [
            'id_producto' => $p->id_producto, 'nombre' => $p->nombre, 'precio' => (float) $p->precio,
            'adiciones' => $p->adiciones->map(fn ($a) => ['id_adicion' => $a->id_adicion, 'nombre' => $a->nombre, 'precio' => (float) $a->precio, 'activa' => $a->activa]),
        ]), 'promociones' => Promocion::with('productos:id_producto,nombre')->latest()->get()]);
    }

    public function crearAdicion(Request $request): JsonResponse
    {
        $data = $request->validate(['id_producto' => 'required|exists:productos,id_producto', 'nombre' => 'required|string|max:100', 'precio' => 'required|numeric|min:0|max:10000000']);
        $adicion = AdicionProducto::create($data + ['activa' => true]);
        return response()->json(['data' => $adicion], 201);
    }

    public function estadoAdicion(Request $request, AdicionProducto $adicion): JsonResponse
    {
        $data = $request->validate(['activa' => 'required|boolean']);
        $adicion->update($data);
        return response()->json(['data' => $adicion]);
    }

    public function crear(Request $request): JsonResponse
    {
        $data = $request->validate(['nombre' => 'required|string|max:120', 'dia_semana' => 'required|integer|min:1|max:7',
            'descuento_porcentaje' => 'required|numeric|min:0.01|max:15', 'fecha_inicio' => 'nullable|date', 'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'productos' => 'required|array|min:1', 'productos.*' => 'integer|distinct|exists:productos,id_producto']);
        $promo = Promocion::create(collect($data)->except('productos')->all());
        $promo->productos()->sync($data['productos']);
        return response()->json(['data' => $promo->load('productos:id_producto,nombre')], 201);
    }

    public function eliminar(Promocion $promocion): JsonResponse
    {
        $promocion->delete();
        return response()->json(['message' => 'Promoción eliminada.']);
    }
}
