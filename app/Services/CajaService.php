<?php

namespace App\Services;

use App\Models\CierreCaja;
use App\Models\Pago;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CajaService
{
    /**
     * Resumen de las ventas del día para el cierre de caja del vendedor:
     * ventas registradas y totales por método de pago (solo pagos completados).
     *
     * @return array{cantidad_ventas: int, total_ventas: float, total_efectivo: float, total_tarjeta: float, total_transferencia: float, total_stripe: float, total_paypal: float, total_otros: float, ya_cerrado: bool}
     */
    public function resumenDelDia(User $usuario): array
    {
        $ventas = Venta::query()
            ->where('user_id', $usuario->id)
            ->whereDate('fecha', today())
            ->where('estado', '!=', 'Cancelado')
            ->get();

        $pagos = Pago::query()
            ->where('estado', 'Completado')
            ->whereHas('venta', fn ($q) => $q
                ->where('user_id', $usuario->id)
                ->whereDate('fecha', today())
                ->where('estado', '!=', 'Cancelado'))
            ->get();

        $totales = [
            'Efectivo' => 0.0,
            'Tarjeta' => 0.0,
            'Transferencia' => 0.0,
            'Stripe' => 0.0,
            'PayPal' => 0.0,
            'Otro' => 0.0,
        ];

        foreach ($pagos as $pago) {
            $totales[$pago->metodo] = round(($totales[$pago->metodo] ?? 0) + (float) $pago->monto, 2);
        }

        return [
            'cantidad_ventas' => $ventas->count(),
            'total_ventas' => round((float) $ventas->sum('total'), 2),
            'total_efectivo' => $totales['Efectivo'],
            'total_tarjeta' => $totales['Tarjeta'],
            'total_transferencia' => $totales['Transferencia'],
            'total_stripe' => $totales['Stripe'],
            'total_paypal' => $totales['PayPal'],
            'total_otros' => $totales['Otro'],
            'ya_cerrado' => CierreCaja::query()
                ->where('user_id', $usuario->id)
                ->whereDate('fecha_cierre', today())
                ->exists(),
        ];
    }

    /**
     * Registra el cierre de caja del día para el vendedor.
     * Un vendedor solo puede cerrar una vez por día.
     */
    public function cerrar(User $usuario, ?string $observacion = null): CierreCaja
    {
        if (CierreCaja::query()
            ->where('user_id', $usuario->id)
            ->whereDate('fecha_cierre', today())
            ->exists()) {
            throw ValidationException::withMessages([
                'cierre' => 'La caja de hoy ya fue cerrada. Un vendedor solo puede cerrar una vez por día.',
            ]);
        }

        $resumen = $this->resumenDelDia($usuario);

        return DB::transaction(function () use ($usuario, $resumen, $observacion) {
            return CierreCaja::create([
                'user_id' => $usuario->id,
                'fecha_cierre' => today(),
                'cantidad_ventas' => $resumen['cantidad_ventas'],
                'total_ventas' => $resumen['total_ventas'],
                'total_efectivo' => $resumen['total_efectivo'],
                'total_tarjeta' => $resumen['total_tarjeta'],
                'total_transferencia' => $resumen['total_transferencia'],
                'total_stripe' => $resumen['total_stripe'],
                'total_paypal' => $resumen['total_paypal'],
                'total_otros' => $resumen['total_otros'],
                'observacion' => $observacion,
            ]);
        });
    }
}
