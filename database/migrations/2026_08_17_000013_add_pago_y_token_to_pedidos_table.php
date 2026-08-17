<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // Pago en línea del catálogo público: método elegido en el checkout,
            // estado del pago y referencia de la pasarela (sesión/orden).
            $table->string('metodo_pago')->nullable();
            $table->enum('estado_pago', ['Pendiente', 'Pagado', 'Fallido'])->default('Pendiente');
            $table->string('referencia_pago')->nullable();

            // Token único para que el cliente confirme/cancele su pedido
            // desde el correo (antes de que el taller lo procese).
            $table->string('token')->nullable()->unique();
            $table->timestamp('cliente_confirmado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['metodo_pago', 'estado_pago', 'referencia_pago', 'token', 'cliente_confirmado_en']);
        });
    }
};
