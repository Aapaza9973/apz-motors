<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respaldos', function (Blueprint $table) {
            $table->id();
            $table->string('archivo')->nullable();
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->enum('estado', ['exitoso', 'fallido']);
            $table->text('mensaje')->nullable();
            $table->timestamp('ejecutado_en');
            $table->timestamps();

            $table->index('ejecutado_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respaldos');
    }
};
