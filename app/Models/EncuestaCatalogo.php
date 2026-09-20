<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Respuesta anónima de la encuesta de mejora del catálogo público.
 *
 * No guarda usuario, IP ni datos personales: solo las respuestas y el
 * punto del catálogo desde el que se respondió (listado o ficha).
 */
class EncuestaCatalogo extends Model
{
    protected $table = 'encuestas_catalogo';

    public const FACILIDADES = ['facil', 'normal', 'dificil'];

    protected $fillable = [
        'satisfaccion',
        'facilidad_encontrar',
        'falta',
        'comentario',
        'origen',
    ];

    protected function casts(): array
    {
        return [
            'satisfaccion' => 'integer',
        ];
    }

    public function etiquetaFacilidad(): string
    {
        return match ($this->facilidad_encontrar) {
            'facil' => 'Me fue fácil encontrar lo que buscaba',
            'normal' => 'Me costó un poco encontrar lo que buscaba',
            'dificil' => 'No encontré lo que buscaba',
            default => $this->facilidad_encontrar,
        };
    }

    public function etiquetaSatisfaccion(): string
    {
        return match (true) {
            $this->satisfaccion <= 2 => 'Insatisfecho',
            $this->satisfaccion === 3 => 'Neutral',
            $this->satisfaccion >= 4 => 'Satisfecho',
            default => '—',
        };
    }
}
