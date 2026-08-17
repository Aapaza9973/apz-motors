<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->timestamp('fecha');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->decimal('total', 12, 2);
            $table->enum('estado', ['Pendiente', 'Pagado', 'Cancelada'])->default('Pendiente');
            $table->timestamps();

            $table->index('fecha');
            $table->index('user_id');
            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
