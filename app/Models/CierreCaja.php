<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CierreCaja extends Model
{
    use HasFactory;

    protected $table = 'cierres_caja';

    protected $fillable = [
        'user_id',
        'fecha_cierre',
        'cantidad_ventas',
        'total_ventas',
        'total_efectivo',
        'total_tarjeta',
        'total_transferencia',
        'total_stripe',
        'total_paypal',
        'total_otros',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_cierre' => 'date',
            'cantidad_ventas' => 'integer',
            'total_ventas' => 'decimal:2',
            'total_efectivo' => 'decimal:2',
            'total_tarjeta' => 'decimal:2',
            'total_transferencia' => 'decimal:2',
            'total_stripe' => 'decimal:2',
            'total_paypal' => 'decimal:2',
            'total_otros' => 'decimal:2',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
