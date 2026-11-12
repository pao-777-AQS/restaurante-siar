<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Portal de clientes: un pedido "para recoger" no tiene mesa, y un pedido hecho
 * por el cliente no tiene mesero hasta que alguien lo atiende.
 *
 * La migración original de "pedidos" ya corrió en las bases existentes (local y
 * servidor) con estas columnas obligatorias, por eso el cambio va en una
 * migración nueva en vez de editar la original.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_mesa')->nullable()->change();
            $table->unsignedBigInteger('id_usuario')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Volver a "obligatorio" fallaría si ya hay pedidos de clientes sin mesa o
        // sin mesero; por eso no se revierte.
    }
};
