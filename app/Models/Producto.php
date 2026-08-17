<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'categoria_id',
        'nombre',
        'descripcion',
        'precio_unitario',
        'costo',
        'stock',
        'umbral_alerta',
    ];

    protected function casts(): array
    {
        return [
            'precio_unitario' => 'decimal:2',
            'costo' => 'decimal:2',
            'stock' => 'integer',
            'umbral_alerta' => 'integer',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoStock::class)->latest();
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(AlertaStock::class)->latest();
    }

    public function tieneStockBajo(): bool
    {
        return $this->stock <= $this->umbral_alerta;
    }
}
