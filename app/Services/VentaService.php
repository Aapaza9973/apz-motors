<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VentaService
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * Crea una venta de forma atómica: valida stock, descuenta inventario,
     * guarda encabezado + detalles y registra el pago si se indica.
     *
     * @param  array{cliente_id?: int|null, estado?: string}  $datos
     * @param  array<int, array{producto_id: int, cantidad: int}>  $items
     * @param  array{monto?: float, metodo?: string}|null  $pago
     */
    public function crearVenta(array $datos, array $items, ?User $usuario, ?array $pago = null): Venta
    {
        if ($usuario === null) {
            throw new \InvalidArgumentException('Se requiere un usuario autenticado para registrar la venta.');
        }

        if (empty($items)) {
            throw ValidationException::withMessages(['items' => 'La venta debe incluir al menos un producto.']);
        }

        return DB::transaction(function () use ($datos, $items, $usuario, $pago) {
            $total = 0;
            $detalles = [];

            foreach ($items as $item) {
                $producto = Producto::lockForUpdate()->findOrFail($item['producto_id']);
                $cantidad = (int) $item['cantidad'];

                if ($cantidad <= 0) {
                    throw ValidationException::withMessages([
                        'items' => "La cantidad del producto {$producto->nombre} debe ser mayor a cero.",
                    ]);
                }

                if ($producto->stock < $cantidad) {
                    throw ValidationException::withMessages([
                        'items' => "Stock insuficiente para \"{$producto->nombre}\" (disponible: {$producto->stock}).",
                    ]);
                }

                $subtotal = round($producto->precio_unitario * $cantidad, 2);
                $total += $subtotal;

                $detalles[] = [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $producto->precio_unitario,
                ];
            }

            $clienteId = $datos['cliente_id'] ?? null;
            if ($clienteId) {
                Cliente::findOrFail($clienteId);
            }

            $venta = Venta::create([
                'fecha' => now(),
                'user_id' => $usuario->id,
                'cliente_id' => $clienteId,
                'total' => round($total, 2),
                'estado' => $datos['estado'] ?? 'Pendiente',
            ]);

            foreach ($detalles as $detalle) {
                /** @var Producto $producto */
                $producto = $detalle['producto'];

                $venta->detalles()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                ]);

                // Descuenta stock y genera alertas de reposición si aplica.
                $this->inventory->ajustarStock(
                    $producto,
                    -$detalle['cantidad'],
                    'venta',
                    "Venta #{$venta->id}",
                    $usuario
                );
            }

            if ($pago !== null && ($pago['monto'] ?? 0) > 0) {
                Pago::create([
                    'venta_id' => $venta->id,
                    'monto' => $pago['monto'],
                    'metodo' => $pago['metodo'] ?? 'Efectivo',
                    'estado' => 'Completado',
                ]);

                if (round((float) $pago['monto'], 2) >= round($venta->total, 2)) {
                    $venta->update(['estado' => 'Pagado']);
                }
            }

            return $venta->load('detalles.producto', 'cliente', 'usuario');
        });
    }
}
