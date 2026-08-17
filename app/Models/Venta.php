<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Venta extends Model
{
    use HasFactory;

    protected $fillable = [
        'fecha',
        'user_id',
        'cliente_id',
        'total',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'total' => 'decimal:2',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    /** Pedido en línea del catálogo que dio origen a esta venta (si existe). */
    public function pedido(): HasOne
    {
        return $this->hasOne(Pedido::class);
    }

    public function estaPagada(): bool
    {
        return $this->estado === 'Pagado';
    }

    public function montoPagado(): string
    {
        return number_format($this->pagos()->where('estado', 'Completado')->sum('monto'), 2);
    }
}
