<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historial de importaciones CSV de productos: cada carga masiva queda
 * registrada con su lote (IMP-…), el resumen de lo que hizo (creados /
 * actualizados / errores), la persona que la ejecutó y el detalle de las
 * filas omitidas. El reporte de movimientos se descarga por lote.
 */
class Importacion extends Model
{
    protected $table = 'importaciones';

    protected $fillable = [
        'lote',
        'creados',
        'actualizados',
        'errores',
        'total_filas',
        'user_id',
        'detalle_errores',
    ];

    protected $casts = [
        'detalle_errores' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
