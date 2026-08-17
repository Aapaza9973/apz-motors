<?php

namespace App\Services;

use App\Models\Devolucion;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Validation\ValidationException;

class DevolucionService
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * Registra una solicitud de devolución validando contra la venta original.
     */
    public function crear(Venta $venta, array $datos, ?User $usuario): Devolucion
    {
        if ($venta->estado === 'Cancelada') {
            throw ValidationException::withMessages(['venta_id' => 'No se puede devolver un producto de una venta cancelada.']);
        }

        $productoId = (int) $datos['producto_id'];
        $cantidad = (int) $datos['cantidad'];

        $detalle = $venta->detalles()->where('producto_id', $productoId)->first();

        if ($detalle === null) {
            throw ValidationException::withMessages(['producto_id' => 'El producto no forma parte de esta venta.']);
        }

        $vendido = $detalle->cantidad;
        $yaDevuelto = Devolucion::where('venta_id', $venta->id)
            ->where('producto_id', $productoId)
            ->where('estado', '!=', 'Rechazada')
            ->sum('cantidad');

        $disponible = $vendido - $yaDevuelto;

        if ($cantidad <= 0) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad debe ser mayor a cero.']);
        }

        if ($cantidad > $disponible) {
            throw ValidationException::withMessages([
                'cantidad' => "Solo se pueden devolver {$disponible} unidad(es) de este producto en esta venta.",
            ]);
        }

        return Devolucion::create([
            'venta_id' => $venta->id,
            'producto_id' => $productoId,
            'cantidad' => $cantidad,
            'motivo' => $datos['motivo'],
            'estado' => 'Pendiente',
            'user_id' => $usuario?->id,
        ]);
    }

    /**
     * Aprueba la devolución: repone stock, registra el movimiento y
     * calcula el monto a reembolsar con el precio de la venta original.
     */
    public function aprobar(Devolucion $devolucion, ?float $montoOverride = null): Devolucion
    {
        $this->asegurarPendiente($devolucion);

        $detalle = $devolucion->venta->detalles()
            ->where('producto_id', $devolucion->producto_id)
            ->first();

        $monto = $montoOverride !== null
            ? round($montoOverride, 2)
            : round($detalle->precio_unitario * $devolucion->cantidad, 2);

        // Repone el stock dentro de la misma transacción.
        \Illuminate\Support\Facades\DB::transaction(function () use ($devolucion, $monto) {
            $this->inventory->ajustarStock(
                $devolucion->producto,
                $devolucion->cantidad,
                'devolucion',
                "Devolución venta #{$devolucion->venta_id}",
                $devolucion->usuario
            );

            $devolucion->update([
                'estado' => 'Aprobada',
                'monto_reembolso' => $monto,
            ]);
        });

        return $devolucion->fresh();
    }

    public function rechazar(Devolucion $devolucion, ?string $motivo = null): Devolucion
    {
        $this->asegurarPendiente($devolucion);

        $devolucion->update([
            'estado' => 'Rechazada',
            'motivo' => $motivo ? $devolucion->motivo."\n[Rechazo] ".$motivo : $devolucion->motivo,
        ]);

        return $devolucion->fresh();
    }

    private function asegurarPendiente(Devolucion $devolucion): void
    {
        if ($devolucion->estado !== 'Pendiente') {
            throw new \DomainException('La devolución ya fue procesada.');
        }
    }
}
