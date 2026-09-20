<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Punto;
use App\Models\User;
use App\Models\Venta;
use App\Services\DevolucionService;
use App\Services\PaymentService;
use App\Services\PuntosService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PuntosTest extends TestCase
{
    use RefreshDatabase;

    private User $vendedor;

    private User $admin;

    private Cliente $cliente;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->vendedor = User::factory()->create()->assignRole('Vendedor');
        $this->admin = User::factory()->create()->assignRole('Admin');

        $categoria = Categoria::create(['nombre' => 'Frenos', 'tipo' => 'Repuesto']);
        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Pastilla de freno',
            'precio_unitario' => 55.00,
            'stock' => 50,
            'umbral_alerta' => 5,
        ]);

        $this->cliente = Cliente::create(['nombre' => 'Cliente fidelizado']);
    }

    private function registrarVentaPagada(float $monto, int $puntosCanje = 0, int $cantidad = 2): Venta
    {
        $this->actingAs($this->vendedor)->post('/ventas', [
            'cliente_id' => $this->cliente->id,
            'items' => [
                ['producto_id' => $this->producto->id, 'cantidad' => $cantidad],
            ],
            'puntos_canje' => $puntosCanje,
            'pago_monto' => $monto,
            'pago_metodo' => 'Efectivo',
        ]);

        return Venta::latest('id')->first();
    }

    // ----------------------------------------------------------------------
    // Acumulación
    // ----------------------------------------------------------------------

    public function test_venta_pagada_acredita_puntos_al_cliente(): void
    {
        $venta = $this->registrarVentaPagada(110.00);

        $this->assertEquals('Pagado', $venta->estado);
        $this->assertEquals(11, $this->cliente->puntosDisponibles()); // 110 / 10

        $punto = Punto::where('cliente_id', $this->cliente->id)->first();
        $this->assertNotNull($punto);
        $this->assertEquals('acumulado', $punto->tipo);
        $this->assertEquals(11, $punto->puntos);
        $this->assertEquals($venta->id, $punto->venta_id);
        $this->assertStringContainsString('venta #' . $venta->id, $punto->concepto);
    }

    public function test_la_acumulacion_es_idempotente_por_venta(): void
    {
        $venta = $this->registrarVentaPagada(110.00);

        app(PuntosService::class)->acumularPorVenta($venta->fresh(), $this->vendedor);

        $this->assertEquals(11, $this->cliente->puntosDisponibles());
        $this->assertDatabaseCount('puntos', 1);
    }

    public function test_venta_sin_cliente_no_acredita_puntos(): void
    {
        $this->actingAs($this->vendedor)->post('/ventas', [
            'items' => [
                ['producto_id' => $this->producto->id, 'cantidad' => 1],
            ],
            'pago_monto' => 55.00,
            'pago_metodo' => 'Efectivo',
        ]);

        $this->assertDatabaseCount('puntos', 0);
    }

    public function test_venta_pendiente_no_acredita_puntos(): void
    {
        $this->actingAs($this->vendedor)->post('/ventas', [
            'cliente_id' => $this->cliente->id,
            'items' => [
                ['producto_id' => $this->producto->id, 'cantidad' => 1],
            ],
        ]);

        $this->assertDatabaseCount('puntos', 0);
    }

    public function test_venta_menor_al_umbral_no_acredita_puntos(): void
    {
        $this->actingAs($this->vendedor)->post('/ventas', [
            'cliente_id' => $this->cliente->id,
            'items' => [
                ['producto_id' => $this->producto->id, 'cantidad' => 1],
            ],
            'pago_monto' => 9.00,
            'pago_metodo' => 'Efectivo',
        ]);

        $this->assertDatabaseCount('puntos', 0);
    }

    // ----------------------------------------------------------------------
    // Canje en el punto de venta
    // ----------------------------------------------------------------------

    public function test_canje_de_puntos_aplica_descuento_al_total(): void
    {
        // El cliente acumula 100 puntos (Bs 100 de compras previas).
        Punto::create([
            'cliente_id' => $this->cliente->id,
            'tipo' => 'acumulado',
            'puntos' => 100,
            'concepto' => 'Compras previas',
        ]);

        $venta = $this->registrarVentaPagada(45.00, puntosCanje: 100, cantidad: 1);

        // 100 puntos = Bs 10 de descuento → total 55 - 10 = 45
        $this->assertEquals(45.00, (float) $venta->total);
        $this->assertEquals(10.00, (float) $venta->descuento);
        $this->assertEquals(100, $venta->puntos_canjeados);

        // Saldo: 100 (acumulados) - 100 (canje) + 4 (ganados por el total pagado 45/10) = 4
        $this->assertEquals(4, $this->cliente->puntosDisponibles());

        // Movimiento de canje registrado
        $canje = Punto::where('cliente_id', $this->cliente->id)
            ->where('tipo', 'canjeado')
            ->first();
        $this->assertNotNull($canje);
        $this->assertEquals(-100, $canje->puntos);
    }

    public function test_canje_sin_saldo_suficiente_es_rechazado(): void
    {
        Punto::create([
            'cliente_id' => $this->cliente->id,
            'tipo' => 'acumulado',
            'puntos' => 30,
            'concepto' => 'Compras previas',
        ]);

        $response = $this->actingAs($this->vendedor)->post('/ventas', [
            'cliente_id' => $this->cliente->id,
            'items' => [
                ['producto_id' => $this->producto->id, 'cantidad' => 1],
            ],
            'puntos_canje' => 100,
            'pago_monto' => 55.00,
            'pago_metodo' => 'Efectivo',
        ]);

        $response->assertSessionHasErrors('puntos_canje');
        $this->assertDatabaseCount('ventas', 0);
        $this->assertEquals(30, $this->cliente->puntosDisponibles());
    }

    public function test_canje_sin_cliente_es_rechazado(): void
    {
        $response = $this->actingAs($this->vendedor)->post('/ventas', [
            'items' => [
                ['producto_id' => $this->producto->id, 'cantidad' => 1],
            ],
            'puntos_canje' => 10,
            'pago_monto' => 55.00,
            'pago_metodo' => 'Efectivo',
        ]);

        $response->assertSessionHasErrors('puntos_canje');
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_el_descuento_nunca_deja_el_total_negativo(): void
    {
        Punto::create([
            'cliente_id' => $this->cliente->id,
            'tipo' => 'acumulado',
            'puntos' => 1000,
            'concepto' => 'Compras previas',
        ]);

        $venta = $this->registrarVentaPagada(0.00, puntosCanje: 1000, cantidad: 1);

        // 1000 puntos = Bs 100 de descuento, pero el total es 55 → queda en 0
        $this->assertEquals(0.00, (float) $venta->total);
        $this->assertEquals(55.00, (float) $venta->descuento);
    }

    // ----------------------------------------------------------------------
    // Ajustes manuales
    // ----------------------------------------------------------------------

    public function test_ajuste_manual_suma_puntos_con_motivo(): void
    {
        $this->actingAs($this->admin)->post(route('clientes.puntos.ajustar', $this->cliente), [
            'puntos' => 50,
            'motivo' => 'Bonificación por fidelidad',
        ]);

        $this->assertEquals(50, $this->cliente->puntosDisponibles());

        $ajuste = Punto::where('cliente_id', $this->cliente->id)->first();
        $this->assertEquals('ajuste', $ajuste->tipo);
        $this->assertEquals(50, $ajuste->puntos);
        $this->assertEquals('Bonificación por fidelidad', $ajuste->concepto);
        $this->assertEquals($this->admin->id, $ajuste->user_id);
    }

    public function test_ajuste_manual_resta_puntos(): void
    {
        Punto::create([
            'cliente_id' => $this->cliente->id,
            'tipo' => 'acumulado',
            'puntos' => 100,
            'concepto' => 'Compras previas',
        ]);

        $this->actingAs($this->admin)->post(route('clientes.puntos.ajustar', $this->cliente), [
            'puntos' => -30,
            'motivo' => 'Corrección de saldo',
        ]);

        $this->assertEquals(70, $this->cliente->puntosDisponibles());
    }

    public function test_ajuste_con_cero_puntos_es_rechazado(): void
    {
        $response = $this->actingAs($this->admin)->post(route('clientes.puntos.ajustar', $this->cliente), [
            'puntos' => 0,
            'motivo' => 'Sin efecto',
        ]);

        $response->assertSessionHasErrors('puntos');
        $this->assertDatabaseCount('puntos', 0);
    }

    public function test_ajuste_sin_motivo_es_rechazado(): void
    {
        $response = $this->actingAs($this->admin)->post(route('clientes.puntos.ajustar', $this->cliente), [
            'puntos' => 10,
        ]);

        $response->assertSessionHasErrors('motivo');
        $this->assertDatabaseCount('puntos', 0);
    }

    public function test_vendedor_no_puede_ajustar_puntos(): void
    {
        $this->actingAs($this->vendedor)
            ->post(route('clientes.puntos.ajustar', $this->cliente), [
                'puntos' => 10,
                'motivo' => 'Intento sin permiso',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('puntos', 0);
    }

    // ----------------------------------------------------------------------
    // Reversión por devolución
    // ----------------------------------------------------------------------

    public function test_aprobar_devolucion_revierte_puntos_proporcionalmente(): void
    {
        // Venta pagada de Bs 110 → 11 puntos acumulados.
        $venta = $this->registrarVentaPagada(110.00);
        $this->assertEquals(11, $this->cliente->puntosDisponibles());

        // Devolución de 1 unidad (Bs 55 de 110) → revierte la mitad: 5.5 → 6 (redondeo).
        $devolucion = app(DevolucionService::class)->crear($venta, [
            'producto_id' => $this->producto->id,
            'cantidad' => 1,
            'motivo' => 'Producto defectuoso',
        ], $this->vendedor);

        $this->actingAs($this->admin)->post(route('devoluciones.aprobar', $devolucion));

        $this->assertEquals(5, $this->cliente->puntosDisponibles()); // 11 - 6

        $reversion = Punto::where('cliente_id', $this->cliente->id)
            ->where('tipo', 'ajuste')
            ->where('puntos', '<', 0)
            ->first();
        $this->assertNotNull($reversion);
        $this->assertEquals(-6, $reversion->puntos);
        $this->assertStringContainsString('Devolución aprobada', $reversion->concepto);
    }

    public function test_devolucion_rechazada_no_revierte_puntos(): void
    {
        $venta = $this->registrarVentaPagada(110.00);
        $this->assertEquals(11, $this->cliente->puntosDisponibles());

        $devolucion = app(DevolucionService::class)->crear($venta, [
            'producto_id' => $this->producto->id,
            'cantidad' => 1,
            'motivo' => 'Prueba',
        ], $this->vendedor);

        $this->actingAs($this->admin)->post(route('devoluciones.rechazar', $devolucion));

        $this->assertEquals(11, $this->cliente->puntosDisponibles());
    }

    // ----------------------------------------------------------------------
    // Acreditación al confirmar pago en línea
    // ----------------------------------------------------------------------

    public function test_confirmar_pago_en_linea_acredita_puntos(): void
    {
        $venta = Venta::create([
            'fecha' => now(),
            'user_id' => $this->vendedor->id,
            'cliente_id' => $this->cliente->id,
            'total' => 100.00,
            'estado' => 'Pendiente',
        ]);

        app(PaymentService::class)->confirmar($venta, 'Stripe', 'pi_test_123');

        $this->assertEquals('Pagado', $venta->fresh()->estado);
        $this->assertEquals(10, $this->cliente->puntosDisponibles()); // 100 / 10
    }

    // ----------------------------------------------------------------------
    // Vista de cliente
    // ----------------------------------------------------------------------

    public function test_vista_de_cliente_muestra_saldo_y_movimientos(): void
    {
        Punto::create([
            'cliente_id' => $this->cliente->id,
            'tipo' => 'acumulado',
            'puntos' => 25,
            'concepto' => 'Compras previas',
        ]);

        $this->actingAs($this->admin)
            ->get(route('clientes.show', $this->cliente))
            ->assertOk()
            ->assertSee('25')
            ->assertSee('Compras previas');
    }
}
