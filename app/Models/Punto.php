<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimiento de puntos de fidelización de un cliente.
 * El saldo del cliente es la suma de la columna `puntos`
 * (positivo acumula, negativo canjea/revierte).
 */
class Punto extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'venta_id',
        'user_id',
        'tipo',
        'puntos',
        'concepto',
    ];

    protected function casts(): array
    {
        return [
            'puntos' => 'integer',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
