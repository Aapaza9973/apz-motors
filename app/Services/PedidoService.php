<?php

namespace App\Services;

use App\Jobs\NotificarClientePedido;
use App\Jobs\NotificarTallerPedido;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PedidoService
{
    public function __construct(private VentaService $ventas) {}

    /**
     * Registra un pedido del catálogo público con los datos del cliente.
     * Valida stock contra el inventario actual y guarda una instantánea
     * del nombre y precio de cada producto.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<int, int>  $items  producto_id => cantidad
     */
    public function crearPedido(array $datos, array $items): Pedido
    {
        if (empty($items)) {
            throw ValidationException::withMessages(['items' => 'El carrito está vacío. Agregá repuestos antes de hacer el pedido.']);
        }

        $pedido = DB::transaction(function () use ($datos, $items) {
            $total = 0;
            $lineas = [];

            foreach ($items as $productoId => $cantidad) {
                $producto = Producto::lockForUpdate()->find($productoId);
                $cantidad = (int) $cantidad;

                if ($producto === null || $cantidad <= 0) {
                    continue;
                }

                if ($producto->stock < $cantidad) {
                    throw ValidationException::withMessages([
                        'items' => "Stock insuficiente para \"{$producto->nombre}\" (disponible: {$producto->stock}). Reducí la cantidad o consultá en el taller.",
                    ]);
                }

                $total += round((float) $producto->precio_unitario * $cantidad, 2);

                $lineas[] = [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                ];
            }

            if (empty($lineas)) {
                throw ValidationException::withMessages(['items' => 'El carrito está vacío. Agregá repuestos antes de hacer el pedido.']);
            }

            $pedido = Pedido::create([
                'nombre_cliente' => $datos['nombre_cliente'],
                'telefono' => $datos['telefono'],
                'email' => $datos['email'] ?? null,
                'direccion' => $datos['direccion'] ?? null,
                'nota' => $datos['nota'] ?? null,
                'total' => round($total, 2),
                'estado' => 'Pendiente',
                'metodo_pago' => $datos['metodo_pago'] ?? 'Efectivo',
                'estado_pago' => 'Pendiente',
                // Token del correo para que el cliente confirme/cancele
                // su pedido antes de que el taller lo procese.
                'token' => Str::random(48),
            ]);

            foreach ($lineas as $linea) {
                /** @var Producto $producto */
                $producto = $linea['producto'];

                $pedido->items()->create([
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'precio_unitario' => $producto->precio_unitario,
                    'cantidad' => $linea['cantidad'],
                ]);
            }

            return $pedido->load('items');
        });

        // Las notificaciones van a la cola después del commit: si el pedido
        // no se registra, nadie recibe avisos por uno inexistente, y el
        // checkout no espera al correo del cliente ni al aviso del taller.
        NotificarClientePedido::dispatch($pedido->id, 'recibido');
        NotificarTallerPedido::dispatch($pedido->id);

        return $pedido;
    }

    /**
     * Confirma el pedido desde la bandeja interna: revalida el stock actual,
     * registra (o reutiliza) el cliente y genera la venta que descuenta
     * inventario. El pedido queda ligado a la venta resultante.
     */
    public function confirmar(Pedido $pedido, User $usuario): Venta
    {
        if (! $pedido->estaPendiente()) {
            throw ValidationException::withMessages([
                'pedido' => "El pedido #{$pedido->id} ya fue procesado.",
            ]);
        }

        $venta = DB::transaction(function () use ($pedido, $usuario) {
            $items = $pedido->items()->get()->map(fn ($item) => [
                'producto_id' => $item->producto_id,
                'cantidad' => $item->cantidad,
            ])->all();

            $cliente = $this->obtenerOCrearCliente($pedido);

            // Si el cliente pagó en línea en el checkout, la venta nace
            // con el pago Completado y queda directamente Pagada.
            $pago = $pedido->estaPagado()
                ? [
                    'monto' => $pedido->total,
                    'metodo' => $pedido->metodo_pago ?? 'Efectivo',
                    'referencia' => $pedido->referencia_pago,
                ]
                : null;

            $venta = $this->ventas->crearVenta(
                ['cliente_id' => $cliente?->id, 'estado' => 'Pendiente'],
                $items,
                $usuario,
                $pago
            );

            $pedido->update([
                'estado' => 'Confirmado',
                'user_id' => $usuario->id,
                'venta_id' => $venta->id,
            ]);

            return $venta;
        });

        NotificarClientePedido::dispatch($pedido->id, 'confirmado', $venta->id);

        return $venta;
    }

    public function cancelar(Pedido $pedido, User $usuario): void
    {
        if (! $pedido->estaPendiente()) {
            throw ValidationException::withMessages([
                'pedido' => "El pedido #{$pedido->id} ya fue procesado.",
            ]);
        }

        $pedido->update([
            'estado' => 'Cancelado',
            'user_id' => $usuario->id,
        ]);

        NotificarClientePedido::dispatch($pedido->id, 'cancelado');
    }

    /**
     * El cliente confirma su pedido desde el correo (enlace con token):
     * la orden sigue Pendiente para el taller, pero queda registrado que
     * el cliente la ratificó — prioridad a la hora de procesar.
     */
    public function confirmarPorCliente(Pedido $pedido, string $token): void
    {
        if (! $pedido->tokenValido($token)) {
            throw new \DomainException('El enlace no es válido o ya fue utilizado.');
        }

        if (! $pedido->estaPendiente()) {
            throw ValidationException::withMessages([
                'pedido' => "El pedido #{$pedido->id} ya fue procesado por el taller.",
            ]);
        }

        $pedido->update(['cliente_confirmado_en' => now()]);
    }

    /**
     * El cliente cancela su pedido desde el correo (enlace con token).
     * Solo puede hacerlo mientras el taller no lo haya procesado.
     */
    public function cancelarPorCliente(Pedido $pedido, string $token): void
    {
        if (! $pedido->tokenValido($token)) {
            throw new \DomainException('El enlace no es válido o ya fue utilizado.');
        }

        if (! $pedido->estaPendiente()) {
            throw ValidationException::withMessages([
                'pedido' => "El pedido #{$pedido->id} ya fue procesado por el taller y no se puede cancelar.",
            ]);
        }

        $pedido->update(['estado' => 'Cancelado']);

        NotificarClientePedido::dispatch($pedido->id, 'cancelado');
    }

    /**
     * Convierte los datos del pedido en un cliente del sistema: reutiliza
     * por email (o teléfono) si ya existe, para no duplicar fichas.
     */
    private function obtenerOCrearCliente(Pedido $pedido): ?Cliente
    {
        if ($pedido->email) {
            return Cliente::firstOrCreate(
                ['email' => $pedido->email],
                [
                    'nombre' => $pedido->nombre_cliente,
                    'telefono' => $pedido->telefono,
                    'direccion' => $pedido->direccion,
                ]
            );
        }

        if ($pedido->telefono) {
            $existente = Cliente::where('telefono', $pedido->telefono)->first();

            if ($existente) {
                return $existente;
            }
        }

        return Cliente::create([
            'nombre' => $pedido->nombre_cliente,
            'telefono' => $pedido->telefono,
            'email' => $pedido->email,
            'direccion' => $pedido->direccion,
        ]);
    }
}
