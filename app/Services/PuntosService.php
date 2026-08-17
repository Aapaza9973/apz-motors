<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Punto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Fidelización de clientes: acumulación por compra pagada, canje como
 * descuento en el punto de venta, ajustes manuales (correcciones) y
 * reversión proporcional cuando se aprueba una devolución.
 */
class PuntosService
{
    /** Bolivianos de compra necesarios para ganar 1 punto. */
    public function bsPorPunto(): int
    {
        return max(1, (int) config('puntos.bs_por_punto', 10));
    }

    /** Puntos necesarios para descontar Bs 1 en una venta. */
    public function puntosPorBsDescuento(): int
    {
        return max(1, (int) config('puntos.puntos_por_bs_descuento', 10));
    }

    /** Descuento en Bs que genera un canje de puntos. */
    public function descuentoPorPuntos(int $puntos): float
    {
        return round($puntos / $this->puntosPorBsDescuento(), 2);
    }

    /** Puntos que gana un monto de compra. */
    public function puntosPorMonto(float $monto): int
    {
        return (int) floor(max(0, $monto) / $this->bsPorPunto());
    }

    /**
     * Acredita los puntos de una venta Pagada. Idempotente: si la venta ya
     * acreditó puntos no duplica. Sin cliente o sin estado Pagado, no hace nada.
     */
    public function acumularPorVenta(Venta $venta, ?User $usuario = null): ?Punto
    {
        if ($venta->cliente_id === null || $venta->estado !== 'Pagado') {
            return null;
        }

        $puntos = $this->puntosPorMonto((float) $venta->total);

        if ($puntos <= 0) {
            return null;
        }

        return DB::transaction(function () use ($venta, $usuario, $puntos) {
            $yaAcreditado = Punto::where('venta_id', $venta->id)
                ->where('tipo', 'acumulado')
                ->exists();

            if ($yaAcreditado) {
                return null;
            }

            return Punto::create([
                'cliente_id' => $venta->cliente_id,
                'venta_id' => $venta->id,
                'user_id' => $usuario?->id,
                'tipo' => 'acumulado',
                'puntos' => $puntos,
                'concepto' => "Compra — venta #{$venta->id}",
            ]);
        });
    }

    /**
     * Canjea puntos como descuento en una venta. Valida el saldo con bloqueo
     * de fila (evita carreras) y registra el movimiento negativo. Se ejecuta
     * dentro de la transacción de la venta para que todo quede atómico.
     */
    public function canjearEnVenta(Cliente $cliente, int $puntos, Venta $venta, ?User $usuario = null): Punto
    {
        if ($puntos <= 0) {
            throw ValidationException::withMessages([
                'puntos_canje' => 'La cantidad de puntos a canjear debe ser mayor a cero.',
            ]);
        }

        return DB::transaction(function () use ($cliente, $puntos, $venta, $usuario) {
            $saldo = (int) Punto::where('cliente_id', $cliente->id)->lockForUpdate()->sum('puntos');

            if ($saldo < $puntos) {
                throw ValidationException::withMessages([
                    'puntos_canje' => "El cliente solo tiene {$saldo} punto(s) disponibles y quieres canjear {$puntos}.",
                ]);
            }

            return Punto::create([
                'cliente_id' => $cliente->id,
                'venta_id' => $venta->id,
                'user_id' => $usuario?->id,
                'tipo' => 'canjeado',
                'puntos' => -$puntos,
                'concepto' => "Canje en venta #{$venta->id} (descuento Bs "
                    .number_format($this->descuentoPorPuntos($puntos), 2).')',
            ]);
        });
    }

    /**
     * Ajuste manual de puntos (correcciones, bonificaciones). El motivo es
     * obligatorio para dejar trazabilidad de quién y por qué tocó el saldo.
     */
    public function ajustar(Cliente $cliente, int $puntos, string $motivo, ?User $usuario = null): Punto
    {
        if ($puntos === 0) {
            throw ValidationException::withMessages([
                'puntos' => 'El ajuste debe ser distinto de cero.',
            ]);
        }

        return Punto::create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario?->id,
            'tipo' => 'ajuste',
            'puntos' => $puntos,
            'concepto' => $motivo,
        ]);
    }

    /**
     * Reversa puntos cuando se aprueba una devolución: descuenta la porción
     * proporcional al monto reembolsado sobre el total de la venta.
     */
    public function revertirPorDevolucion(Venta $venta, float $montoReembolso, ?User $usuario = null): ?Punto
    {
        if ($venta->cliente_id === null || (float) $venta->total <= 0 || $montoReembolso <= 0) {
            return null;
        }

        $acumulados = (int) Punto::where('venta_id', $venta->id)
            ->where('tipo', 'acumulado')
            ->sum('puntos');

        if ($acumulados <= 0) {
            return null;
        }

        $proporcion = min(1, $montoReembolso / (float) $venta->total);
        $puntos = (int) round($acumulados * $proporcion);

        if ($puntos <= 0) {
            return null;
        }

        return Punto::create([
            'cliente_id' => $venta->cliente_id,
            'venta_id' => $venta->id,
            'user_id' => $usuario?->id,
            'tipo' => 'ajuste',
            'puntos' => -$puntos,
            'concepto' => "Devolución aprobada de la venta #{$venta->id} (reembolso Bs "
                .number_format($montoReembolso, 2).')',
        ]);
    }
}
