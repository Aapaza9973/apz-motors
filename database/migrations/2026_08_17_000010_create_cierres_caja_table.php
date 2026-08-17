<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierres_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('fecha_cierre');
            $table->unsignedInteger('cantidad_ventas')->default(0);
            $table->decimal('total_ventas', 12, 2)->default(0);
            $table->decimal('total_efectivo', 12, 2)->default(0);
            $table->decimal('total_tarjeta', 12, 2)->default(0);
            $table->decimal('total_transferencia', 12, 2)->default(0);
            $table->decimal('total_stripe', 12, 2)->default(0);
            $table->decimal('total_paypal', 12, 2)->default(0);
            $table->decimal('total_otros', 12, 2)->default(0);
            $table->text('observacion')->nullable();
            $table->timestamps();

            // Un cierre por vendedor y día (el turno diario se corta aquí).
            $table->unique(['user_id', 'fecha_cierre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierres_caja');
    }
};
