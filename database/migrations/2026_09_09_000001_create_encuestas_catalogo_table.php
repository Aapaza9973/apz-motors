<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encuestas_catalogo', function (Blueprint $table) {
            $table->id();
            // Sin user_id ni datos personales: la encuesta es anónima.
            $table->unsignedTinyInteger('satisfaccion');          // 1..5
            $table->string('facilidad_encontrar', 20);             // facil | normal | dificil
            $table->text('falta')->nullable();                     // qué repuestos faltan
            $table->text('comentario')->nullable();                // sugerencia libre
            $table->string('origen', 20)->default('catalogo');     // catalogo | producto
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encuestas_catalogo');
    }
};
