<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('tipo', ['acumulado', 'canjeado', 'ajuste'])->default('acumulado');
            // Positivo suma saldo, negativo resta. El saldo del cliente es la suma.
            $table->integer('puntos');
            $table->string('concepto');
            $table->timestamps();

            $table->index('cliente_id');
            $table->index('venta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puntos');
    }
};
