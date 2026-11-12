<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adiciones_productos', function (Blueprint $table) {
            $table->id('id_adicion');
            $table->foreignId('id_producto')->constrained('productos', 'id_producto')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->decimal('precio', 12, 2)->default(0);
            $table->boolean('activa')->default(true);
            $table->timestamps();
            $table->unique(['id_producto', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adiciones_productos');
    }
};
