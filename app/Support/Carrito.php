<?php

namespace App\Support;

use App\Models\Producto;
use Illuminate\Support\Collection;

/**
 * Carrito de compras del catálogo público, guardado en sesión.
 *
 * El carrito vive como un mapa `[producto_id => cantidad]` en la sesión
 * del visitante; los precios y nombres siempre se resuelven contra el
 * inventario actual al renderizar (nunca se cachean en la sesión).
 */
class Carrito
{
    public const CLAVE = 'carrito';

    public const MAX_CANTIDAD = 99;

    /**
     * @return array<int, int> producto_id => cantidad
     */
    public static function items(): array
    {
        return session(self::CLAVE, []);
    }

    public static function cantidadTotal(): int
    {
        return array_sum(self::items());
    }

    public static function estaVacio(): bool
    {
        return empty(self::items());
    }

    public static function agregar(int $productoId, int $cantidad): void
    {
        $items = self::items();
        $items[$productoId] = min(self::MAX_CANTIDAD, ($items[$productoId] ?? 0) + $cantidad);
        session([self::CLAVE => $items]);
    }

    public static function actualizar(int $productoId, int $cantidad): void
    {
        $items = self::items();

        if (! isset($items[$productoId])) {
            return;
        }

        $items[$productoId] = min(self::MAX_CANTIDAD, max(1, $cantidad));
        session([self::CLAVE => $items]);
    }

    public static function eliminar(int $productoId): void
    {
        $items = self::items();
        unset($items[$productoId]);
        session([self::CLAVE => $items]);
    }

    public static function vaciar(): void
    {
        session()->forget(self::CLAVE);
    }

    /**
     * Detalle del carrito con los productos frescos del inventario.
     *
     * @return Collection<int, array{producto: Producto, cantidad: int}>
     */
    public static function detalle(): Collection
    {
        $items = self::items();

        if (empty($items)) {
            return collect();
        }

        return Producto::whereIn('id', array_keys($items))->get()->map(
            fn (Producto $producto) => ['producto' => $producto, 'cantidad' => $items[$producto->id]]
        );
    }

    public static function total(): float
    {
        return round(self::detalle()->sum(
            fn (array $linea) => (float) $linea['producto']->precio_unitario * $linea['cantidad']
        ), 2);
    }
}
