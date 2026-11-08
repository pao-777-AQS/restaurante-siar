<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mesas', function (Blueprint $table) {
            $table->uuid('codigo_qr')->nullable()->unique();
        });

        foreach (DB::table('mesas')->whereNull('codigo_qr')->orderBy('id_mesa')->cursor() as $mesa) {
            DB::table('mesas')->where('id_mesa', $mesa->id_mesa)->update(['codigo_qr' => (string) Str::uuid()]);
        }
    }

    public function down(): void
    {
        Schema::table('mesas', function (Blueprint $table) {
            $table->dropUnique(['codigo_qr']);
            $table->dropColumn('codigo_qr');
        });
    }
};
