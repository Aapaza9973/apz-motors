<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->enum('tipo', ['bajo_stock'])->default('bajo_stock');
            $table->string('mensaje', 255)->nullable();
            $table->boolean('leida')->default(false);
            $table->timestamps();

            $table->index('leida');
            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas_stock');
    }
};
