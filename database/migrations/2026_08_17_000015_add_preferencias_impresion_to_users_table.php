<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Preferencias de impresión por usuario:
     *  - pref_papel_comprobante: 'termico' (80 mm) o 'carta' (A4).
     *  - pref_imprimir_pos: autoimprimir el comprobante al confirmar en el POS.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pref_papel_comprobante', 10)->default('termico')->after('remember_token');
            $table->boolean('pref_imprimir_pos')->default(true)->after('pref_papel_comprobante');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pref_papel_comprobante', 'pref_imprimir_pos']);
        });
    }
};
