<?php

namespace Database\Seeders;

use App\Models\AdicionProducto;
use App\Models\Categoria;
use App\Models\Mesa;
use App\Models\Producto;
use Illuminate\Database\Seeder;

/** Datos únicamente para probar la experiencia del cliente en desarrollo. */
class DemoMenuSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            'Entradas' => 'Platos para empezar.',
            'Platos fuertes' => 'Platos principales.',
            'Bebidas' => 'Bebidas frías y calientes.',
            'Postres' => 'Dulces para terminar.',
        ];

        foreach ($categorias as $nombre => $descripcion) {
            Categoria::firstOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion, 'activa' => true]);
        }

        $catalogo = [
            ['[DEMO] Patacones con hogao', 'Entradas', 'Patacones crujientes con hogao de la casa.', 8000],
            ['[DEMO] Arepa rellena', 'Entradas', 'Arepa dorada rellena de queso.', 12500],
            ['[DEMO] Hamburguesa de la casa', 'Platos fuertes', 'Carne a la plancha, queso, vegetales y salsa de la casa.', 25000],
            ['[DEMO] Bandeja SIAR', 'Platos fuertes', 'Una muestra de plato fuerte para probar el pedido.', 32000],
            ['[DEMO] Limonada natural', 'Bebidas', 'Limonada fresca preparada al momento.', 8000],
            ['[DEMO] Jugo de temporada', 'Bebidas', 'Jugo natural de fruta de temporada.', 12500],
            ['[DEMO] Postre del día', 'Postres', 'Porción dulce de muestra.', 18900],
        ];

        foreach ($catalogo as [$nombre, $categoriaNombre, $descripcion, $precio]) {
            $categoria = Categoria::where('nombre', $categoriaNombre)->firstOrFail();
            $producto = Producto::updateOrCreate(['nombre' => $nombre], [
                'descripcion' => $descripcion,
                'precio' => $precio,
                'disponible' => true,
                'activo' => true,
                'id_categoria' => $categoria->id_categoria,
            ]);

            if ($nombre === '[DEMO] Hamburguesa de la casa') {
                AdicionProducto::firstOrCreate(['id_producto' => $producto->id_producto, 'nombre' => 'Queso extra'], ['precio' => 3000, 'activa' => true]);
                AdicionProducto::firstOrCreate(['id_producto' => $producto->id_producto, 'nombre' => 'Tocineta'], ['precio' => 4500, 'activa' => true]);
            }
        }

        Mesa::firstOrCreate(['numero' => 1], ['capacidad' => 4, 'ubicacion' => 'Salón', 'estado' => 'libre', 'activo' => true]);
    }
}
