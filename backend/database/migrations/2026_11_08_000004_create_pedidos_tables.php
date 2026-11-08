<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id('id_pedido');
            $table->uuid('codigo_seguimiento')->unique();
            $table->string('tipo_pedido', 20); // mesa | recoger
            $table->foreignId('id_mesa')->nullable()->constrained('mesas', 'id_mesa')->nullOnDelete();
            $table->foreignId('id_usuario')->nullable()->constrained('usuarios', 'id_usuario')->nullOnDelete(); // mesero asignado
            $table->string('cliente_nombre', 160);
            $table->string('cliente_telefono', 40);
            $table->string('estado', 30)->default('pendiente');
            $table->unsignedSmallInteger('tiempo_estimado_minutos')->nullable();
            $table->timestamp('estimado_listo_en')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('propina', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('estado_pago', 30)->default('pendiente');
            $table->timestamps();
            $table->index(['estado', 'created_at']);
            $table->index(['tipo_pedido', 'created_at']);
        });

        Schema::create('detalle_pedidos', function (Blueprint $table) {
            $table->id('id_detalle_pedido');
            $table->foreignId('id_pedido')->constrained('pedidos', 'id_pedido')->cascadeOnDelete();
            $table->foreignId('id_producto')->nullable()->constrained('productos', 'id_producto')->nullOnDelete();
            $table->string('nombre_producto', 160); // snapshot para conservar el recibo histórico
            $table->decimal('precio_unitario', 12, 2);
            $table->unsignedSmallInteger('cantidad');
            $table->text('nota')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('descuento_porcentaje', 5, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('detalle_pedido_adiciones', function (Blueprint $table) {
            $table->id('id_detalle_adicion');
            $table->foreignId('id_detalle_pedido')->constrained('detalle_pedidos', 'id_detalle_pedido')->cascadeOnDelete();
            $table->foreignId('id_adicion')->nullable()->constrained('adiciones_productos', 'id_adicion')->nullOnDelete();
            $table->string('nombre', 100);
            $table->decimal('precio', 12, 2);
            $table->timestamps();
        });

        Schema::create('mensajes_pedido', function (Blueprint $table) {
            $table->id('id_mensaje');
            $table->foreignId('id_pedido')->constrained('pedidos', 'id_pedido')->cascadeOnDelete();
            $table->foreignId('id_usuario')->nullable()->constrained('usuarios', 'id_usuario')->nullOnDelete();
            $table->string('autor', 20); // cliente | personal
            $table->string('clasificacion', 30)->default('recomendacion');
            $table->string('visibilidad', 20)->default('privado'); // privado | publico
            $table->string('estado_publicacion', 20)->default('no_aplica'); // pendiente | publicado | oculto | no_aplica
            $table->text('mensaje');
            $table->timestamp('leido_en')->nullable();
            $table->timestamps();
            $table->index(['id_pedido', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes_pedido');
        Schema::dropIfExists('detalle_pedido_adiciones');
        Schema::dropIfExists('detalle_pedidos');
        Schema::dropIfExists('pedidos');
    }
};
