<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre_cliente',
        'telefono',
        'email',
        'direccion',
        'nota',
        'total',
        'estado',
        'user_id',
        'venta_id',
        'metodo_pago',
        'estado_pago',
        'referencia_pago',
        'token',
        'cliente_confirmado_en',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'cliente_confirmado_en' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PedidoItem::class);
    }

    /** Usuario que confirmó (o canceló) el pedido en la bandeja interna. */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function estaPendiente(): bool
    {
        return $this->estado === 'Pendiente';
    }

    public function estaPagado(): bool
    {
        return $this->estado_pago === 'Pagado';
    }

    /** El cliente confirmó el pedido desde el correo (antes del taller). */
    public function clienteConfirmo(): bool
    {
        return $this->cliente_confirmado_en !== null;
    }

    /** Verifica el token del correo de forma segura (comparación de hash). */
    public function tokenValido(?string $token): bool
    {
        return $this->token !== null
            && $token !== null
            && hash_equals($this->token, $token);
    }
}
