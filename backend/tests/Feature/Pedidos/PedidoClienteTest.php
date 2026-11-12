<?php

namespace Tests\Feature\Pedidos;

use App\Enums\EstadoMesa;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Portal de clientes: pedidos públicos por QR de mesa o para recoger. */
class PedidoClienteTest extends TestCase
{
    use RefreshDatabase;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarRolesYPermisos();
        $this->producto = Producto::factory()->create(['nombre' => 'Ajiaco', 'precio' => 24000]);
    }

    private function datos(array $cambios = []): array
    {
        return [
            'tipo_pedido' => 'recoger',
            'cliente_nombre' => 'Laura Gómez',
            'cliente_telefono' => '3001234567',
            'items' => [['id_producto' => $this->producto->id_producto, 'cantidad' => 2]],
            ...$cambios,
        ];
    }

    public function test_un_pedido_para_recoger_no_tiene_mesa_ni_mesero_y_llega_a_cocina(): void
    {
        $respuesta = $this->postJson('/api/cliente/pedidos', $this->datos())
            ->assertCreated()
            ->assertJsonPath('data.tipo_pedido', 'recoger')
            ->assertJsonPath('data.estado', 'pendiente')
            ->assertJsonPath('data.mesa', null);

        $pedido = Pedido::where('codigo_seguimiento', $respuesta->json('data.codigo_seguimiento'))->firstOrFail();
        $this->assertNull($pedido->id_mesa);
        $this->assertNull($pedido->id_usuario);
        $this->assertNotNull($pedido->fecha_envio_cocina, 'Debe quedar en la cola de cocina.');
        $this->assertEquals(48000, $pedido->total());

        // El cliente puede seguir su pedido con el código.
        $this->getJson("/api/cliente/pedidos/{$pedido->codigo_seguimiento}")
            ->assertOk()
            ->assertJsonPath('data.estado', 'pendiente');
    }

    public function test_un_pedido_por_qr_ocupa_la_mesa(): void
    {
        $mesa = Mesa::factory()->create(['numero' => 7]);

        $this->postJson('/api/cliente/pedidos', $this->datos(['tipo_pedido' => 'mesa', 'codigo_qr' => $mesa->codigo_qr]))
            ->assertCreated()
            ->assertJsonPath('data.mesa', 7);

        $this->assertSame(EstadoMesa::OCUPADA, $mesa->fresh()->estado);
    }

    public function test_no_acepta_productos_agotados(): void
    {
        $this->producto->marcarDisponible(false);

        $this->postJson('/api/cliente/pedidos', $this->datos())
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('pedidos', 0);
    }
}
