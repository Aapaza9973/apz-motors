<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->decimal('monto', 12, 2);
            $table->enum('metodo', ['Efectivo', 'Tarjeta', 'Transferencia', 'Stripe', 'PayPal', 'Otro'])->default('Efectivo');
            $table->enum('estado', ['Completado', 'Pendiente', 'Cancelado'])->default('Completado');
            $table->string('referencia', 100)->nullable();
            $table->timestamps();

            $table->index('venta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
