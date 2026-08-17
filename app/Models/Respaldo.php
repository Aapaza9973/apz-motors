<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Respaldo extends Model
{
    use HasFactory;

    protected $fillable = [
        'archivo',
        'tamano_bytes',
        'estado',
        'mensaje',
        'ejecutado_en',
    ];

    protected function casts(): array
    {
        return [
            'tamano_bytes' => 'integer',
            'ejecutado_en' => 'datetime',
        ];
    }

    public function fueExitoso(): bool
    {
        return $this->estado === 'exitoso';
    }
}
