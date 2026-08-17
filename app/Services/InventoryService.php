<?php

namespace App\Services;

use App\Models\AlertaStock;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Ajusta el stock de un producto dentro de la transacción activa,
     * registra el movimiento y genera una alerta si queda bajo el umbral.
     *
     * @param  int  $cantidad  Cantidad positiva para entradas, negativa para salidas.
     */
    public function ajustarStock(
        Producto $producto,
        int $cantidad,
        string $tipo,
        ?string $motivo = null,
        ?User $usuario = null
    ): void {
        // Evitar stock negativo: la validación la hace la capa de negocio,
        // pero aquí se protege la integridad de los datos.
        if ($cantidad < 0 && ($producto->stock + $cantidad) < 0) {
            throw new \DomainException("Stock insuficiente para {$producto->nombre}.");
        }

        $nuevoStock = $producto->stock + $cantidad;

        DB::table('productos')->where('id', $producto->id)->update(['stock' => $nuevoStock]);

        MovimientoStock::create([
            'producto_id' => $producto->id,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'stock_resultante' => $nuevoStock,
            'motivo' => $motivo,
            'user_id' => $usuario?->id,
        ]);

        $producto->stock = $nuevoStock;

        $this->registrarAlertaSiBajaStock($producto);
    }

    /**
     * Genera una alerta de stock bajo si el producto quedó igual o por debajo
     * del umbral y aún no existe una alerta sin leer para ese producto.
     */
    public function registrarAlertaSiBajaStock(Producto $producto): void
    {
        if (! $producto->tieneStockBajo()) {
            return;
        }

        $yaAlertado = AlertaStock::query()
            ->where('producto_id', $producto->id)
            ->where('tipo', 'bajo_stock')
            ->where('leida', false)
            ->exists();

        if (! $yaAlertado) {
            AlertaStock::create([
                'producto_id' => $producto->id,
                'tipo' => 'bajo_stock',
                'mensaje' => "El producto \"{$producto->nombre}\" tiene stock de {$producto->stock} unidades (umbral: {$producto->umbral_alerta}).",
            ]);
        }
    }

    /**
     * Marca como leídas las alertas pendientes de un producto y devuelve cuántas se actualizaron.
     */
    public function limpiarAlertasPendientes(Producto $producto): int
    {
        return AlertaStock::query()
            ->where('producto_id', $producto->id)
            ->where('leida', false)
            ->update(['leida' => true]);
    }
}
