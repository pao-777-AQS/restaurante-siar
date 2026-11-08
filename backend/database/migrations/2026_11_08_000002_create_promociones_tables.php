<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promociones', function (Blueprint $table) {
            $table->id('id_promocion');
            $table->string('nombre', 120);
            $table->unsignedTinyInteger('dia_semana'); // ISO-8601: lunes=1 ... domingo=7
            $table->decimal('descuento_porcentaje', 5, 2);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
            $table->index(['dia_semana', 'activa']);
        });

        Schema::create('promocion_productos', function (Blueprint $table) {
            $table->foreignId('id_promocion')->constrained('promociones', 'id_promocion')->cascadeOnDelete();
            $table->foreignId('id_producto')->constrained('productos', 'id_producto')->cascadeOnDelete();
            $table->primary(['id_promocion', 'id_producto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promocion_productos');
        Schema::dropIfExists('promociones');
    }
};
