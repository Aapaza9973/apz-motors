<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_cliente');
            $table->string('telefono', 30);
            $table->string('email')->nullable();
            $table->string('direccion')->nullable();
            $table->text('nota')->nullable();
            $table->decimal('total', 12, 2);
            $table->enum('estado', ['Pendiente', 'Confirmado', 'Cancelado'])->default('Pendiente');
            // Quién confirmó el pedido y a qué venta dio origen (bandeja interna).
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->timestamps();

            $table->index(['estado', 'created_at']);
            $table->index('venta_id');
        });

        Schema::create('pedido_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained()->cascadeOnDelete();
            // El producto puede desaparecer después; el nombre y precio quedan
            // como instantánea del momento del pedido.
            $table->foreignId('producto_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nombre');
            $table->decimal('precio_unitario', 12, 2);
            $table->unsignedInteger('cantidad');
            $table->timestamps();

            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_items');
        Schema::dropIfExists('pedidos');
    }
};
