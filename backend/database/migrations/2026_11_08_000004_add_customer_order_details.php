<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $legacyPedido = Schema::hasColumn('pedidos', 'created_at');

        Schema::table('pedidos', function (Blueprint $table) use ($legacyPedido) {
            if (! Schema::hasColumn('pedidos', 'codigo_seguimiento')) $table->uuid('codigo_seguimiento')->nullable()->unique();
            if (! Schema::hasColumn('pedidos', 'tipo_pedido')) $table->string('tipo_pedido', 20)->default('mesa');
            if (! Schema::hasColumn('pedidos', 'cliente_nombre')) $table->string('cliente_nombre', 160)->nullable();
            if (! Schema::hasColumn('pedidos', 'cliente_telefono')) $table->string('cliente_telefono', 40)->nullable();
            if (! Schema::hasColumn('pedidos', 'tiempo_estimado_minutos')) $table->unsignedSmallInteger('tiempo_estimado_minutos')->nullable();
            if (! Schema::hasColumn('pedidos', 'estimado_listo_en')) $table->timestamp('estimado_listo_en')->nullable();
            if (! Schema::hasColumn('pedidos', 'subtotal')) $table->decimal('subtotal', 12, 2)->default(0);
            if (! Schema::hasColumn('pedidos', 'descuento')) $table->decimal('descuento', 12, 2)->default(0);
            if (! Schema::hasColumn('pedidos', 'propina')) $table->decimal('propina', 12, 2)->default(0);
            if (! Schema::hasColumn('pedidos', 'estado_pago')) $table->string('estado_pago', 30)->default('pendiente');

            if ($legacyPedido) {
                if (! Schema::hasColumn('pedidos', 'observaciones')) $table->string('observaciones', 255)->nullable();
                if (! Schema::hasColumn('pedidos', 'motivo_cancelacion')) $table->string('motivo_cancelacion', 255)->nullable();
                foreach (['fecha_envio_cocina', 'fecha_inicio_preparacion', 'fecha_listo', 'fecha_entrega', 'fecha_cancelacion', 'fecha_creacion', 'fecha_modificacion'] as $columna) {
                    if (! Schema::hasColumn('pedidos', $columna)) $table->timestamp($columna)->nullable();
                }
            }
        });

        Schema::table('detalle_pedidos', function (Blueprint $table) {
            if (! Schema::hasColumn('detalle_pedidos', 'nombre_producto')) $table->string('nombre_producto', 160)->nullable();
            if (! Schema::hasColumn('detalle_pedidos', 'descuento_porcentaje')) $table->decimal('descuento_porcentaje', 5, 2)->default(0);
            if (! Schema::hasColumn('detalle_pedidos', 'descuento')) $table->decimal('descuento', 12, 2)->default(0);
            if (! Schema::hasColumn('detalle_pedidos', 'observacion')) $table->string('observacion', 255)->nullable();
        });

        if (! Schema::hasTable('detalle_pedido_adiciones')) {
            Schema::create('detalle_pedido_adiciones', function (Blueprint $table) {
                $table->id('id_detalle_adicion');
                $table->foreignId('id_detalle')->constrained('detalle_pedidos', 'id_detalle')->cascadeOnDelete();
                $table->foreignId('id_adicion')->nullable()->constrained('adiciones_productos', 'id_adicion')->nullOnDelete();
                $table->string('nombre', 100);
                $table->decimal('precio', 12, 2);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('mensajes_pedido')) {
            Schema::create('mensajes_pedido', function (Blueprint $table) {
                $table->id('id_mensaje');
                $table->foreignId('id_pedido')->constrained('pedidos', 'id_pedido')->cascadeOnDelete();
                $table->foreignId('id_usuario')->nullable()->constrained('usuarios', 'id_usuario')->nullOnDelete();
                $table->string('autor', 20);
                $table->string('clasificacion', 30)->default('recomendacion');
                $table->string('visibilidad', 20)->default('privado');
                $table->string('estado_publicacion', 20)->default('no_aplica');
                $table->text('mensaje');
                $table->timestamp('leido_en')->nullable();
                $table->timestamps();
                $table->index(['id_pedido', 'created_at']);
            });
        }

        if ($legacyPedido) {
            if (Schema::hasColumn('detalle_pedidos', 'nota')) {
                DB::table('detalle_pedidos')->whereNull('observacion')->update(['observacion' => DB::raw('nota')]);
            }
            DB::table('pedidos')->whereNull('fecha_creacion')->update(['fecha_creacion' => DB::raw('created_at')]);
            DB::table('pedidos')->whereNull('fecha_modificacion')->update(['fecha_modificacion' => DB::raw('updated_at')]);
            DB::table('pedidos')->whereNotNull('codigo_seguimiento')->whereNull('fecha_envio_cocina')
                ->update(['fecha_envio_cocina' => DB::raw('created_at')]);
            DB::table('pedidos')->where('estado', 'preparando')->update(['estado' => 'en_preparacion']);
        }
    }

    public function down(): void
    {
        // Esta extensión puede actualizar pedidos existentes; no se borran datos al revertir.
    }
};
