<?php

namespace Tests\Feature;

use App\Models\AlertaStock;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\VentaService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class VentaTest extends TestCase
{
    use RefreshDatabase;

    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->vendedor = User::factory()->create()->assignRole('Vendedor');
    }

    private function crearProducto(int $stock = 10, int $umbral = 5): Producto
    {
        $categoria = Categoria::create(['nombre' => 'Frenos', 'tipo' => 'Repuesto']);

        return Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Pastillas de freno '.uniqid(),
            'precio_unitario' => 55.00,
            'stock' => $stock,
            'umbral_alerta' => $umbral,
        ]);
    }

    private function crearCliente(): Cliente
    {
        return Cliente::create(['nombre' => 'Cliente de prueba']);
    }

    public function test_registrar_venta_decrementa_stock_y_crea_movimiento(): void
    {
        $producto = $this->crearProducto(stock: 10);
        $cliente = $this->crearCliente();

        $response = $this->actingAs($this->vendedor)->post('/ventas', [
            'cliente_id' => $cliente->id,
            'items' => [
                ['producto_id' => $producto->id, 'cantidad' => 2],
            ],
            'pago_monto' => 110.00,
            'pago_metodo' => 'Efectivo',
        ]);

        $response->assertRedirect();

        $venta = Venta::latest('id')->first();
        $this->assertNotNull($venta);
        $this->assertEquals(110.00, (float) $venta->total);
        $this->assertEquals($cliente->id, $venta->cliente_id);
        $this->assertEquals($this->vendedor->id, $venta->user_id);

        // Stock decrementado: 10 - 2 = 8
        $this->assertEquals(8, $producto->fresh()->stock);

        // Movimiento registrado
        $movimiento = MovimientoStock::where('producto_id', $producto->id)->first();
        $this->assertNotNull($movimiento);
        $this->assertEquals('venta', $movimiento->tipo);
        $this->assertEquals(-2, $movimiento->cantidad);
        $this->assertEquals(8, $movimiento->stock_resultante);

        // Detalle creado con el precio al momento de la venta
        $this->assertEquals(1, $venta->detalles()->count());
        $this->assertEquals(2, $venta->detalles()->first()->cantidad);
        $this->assertEquals('55.00', $venta->detalles()->first()->precio_unitario);
    }

    public function test_venta_con_stock_insuficiente_se_bloquea(): void
    {
        $producto = $this->crearProducto(stock: 2);

        $response = $this->actingAs($this->vendedor)->post('/ventas', [
            'items' => [
                ['producto_id' => $producto->id, 'cantidad' => 5],
            ],
        ]);

        $response->assertSessionHasErrors('items');

        $this->assertDatabaseCount('ventas', 0);
        $this->assertEquals(2, $producto->fresh()->stock);
    }

    public function test_venta_que_deja_stock_bajo_umbral_genera_alerta(): void
    {
        $producto = $this->crearProducto(stock: 10, umbral: 5);

        // Venta de 6 unidades: stock resultante 4 <= umbral 5 → alerta
        $this->actingAs($this->vendedor)->post('/ventas', [
            'items' => [
                ['producto_id' => $producto->id, 'cantidad' => 6],
            ],
        ]);

        $this->assertEquals(4, $producto->fresh()->stock);

        $alerta = AlertaStock::where('producto_id', $producto->id)->where('leida', false)->first();
        $this->assertNotNull($alerta);
        $this->assertEquals('bajo_stock', $alerta->tipo);
        $this->assertStringContainsString('stock de 4 unidades', $alerta->mensaje);
    }

    public function test_venta_no_genera_alerta_si_stock_sigue_sobre_umbral(): void
    {
        $producto = $this->crearProducto(stock: 20, umbral: 5);

        $this->actingAs($this->vendedor)->post('/ventas', [
            'items' => [
                ['producto_id' => $producto->id, 'cantidad' => 2],
            ],
        ]);

        $this->assertEquals(18, $producto->fresh()->stock);
        $this->assertDatabaseCount('alertas_stock', 0);
    }

    public function test_venta_pagada_en_efectivo_queda_como_pagado(): void
    {
        $producto = $this->crearProducto(stock: 5);

        $this->actingAs($this->vendedor)->post('/ventas', [
            'items' => [
                ['producto_id' => $producto->id, 'cantidad' => 1],
            ],
            'pago_monto' => 55.00,
            'pago_metodo' => 'Efectivo',
        ]);

        $venta = Venta::latest('id')->first();
        $this->assertEquals('Pagado', $venta->estado);
        $this->assertEquals(1, $venta->pagos()->count());
        $this->assertEquals('55.00', $venta->pagos()->first()->monto);
    }

    public function test_venta_sin_pago_queda_pendiente(): void
    {
        $producto = $this->crearProducto(stock: 5);

        $this->actingAs($this->vendedor)->post('/ventas', [
            'items' => [
                ['producto_id' => $producto->id, 'cantidad' => 1],
            ],
        ]);

        $venta = Venta::latest('id')->first();
        $this->assertEquals('Pendiente', $venta->estado);
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_venta_sin_productos_es_rechazada(): void
    {
        $response = $this->actingAs($this->vendedor)->post('/ventas', [
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_al_confirmar_desde_el_pos_se_redirige_con_autoimpresion_si_esta_marcada(): void
    {
        $producto = $this->crearProducto(stock: 5);

        $response = $this->actingAs($this->vendedor)->post('/ventas', [
            'items' => [
                ['producto_id' => $producto->id, 'cantidad' => 1],
            ],
            'pago_monto' => 55.00,
            'pago_metodo' => 'Efectivo',
            'imprimir_comprobante' => '1',
        ]);

        $venta = Venta::latest('id')->first();

        // La redirección lleva el parámetro ?imprimir=1 para imprimir el comprobante.
        $response->assertRedirect(route('ventas.show', ['venta' => $venta, 'imprimir' => 1]));
    }

    public function test_al_confirmar_desde_el_pos_sin_autoimpresion_no_lleva_el_parametro(): void
    {
        $producto = $this->crearProducto(stock: 5);

        $response = $this->actingAs($this->vendedor)->post('/ventas', [
            'items' => [
                ['producto_id' => $producto->id, 'cantidad' => 1],
            ],
            'pago_monto' => 55.00,
            'pago_metodo' => 'Efectivo',
        ]);

        $venta = Venta::latest('id')->first();

        $response->assertRedirect(route('ventas.show', ['venta' => $venta]));
    }

    public function test_la_preferencia_de_papel_se_guarda_por_usuario(): void
    {
        $this->actingAs($this->vendedor)
            ->postJson('/preferencias/comprobante', ['papel_comprobante' => 'carta'])
            ->assertOk();

        $this->assertEquals('carta', $this->vendedor->fresh()->pref_papel_comprobante);

        // El comprobante se renderiza con la clase de papel preferida.
        $producto = $this->crearProducto(stock: 5);
        $venta = $this->ventaService()->crearVenta(
            ['estado' => 'Pagado'],
            [['producto_id' => $producto->id, 'cantidad' => 1]],
            $this->vendedor,
            ['monto' => 55.00, 'metodo' => 'Efectivo']
        );

        $this->actingAs($this->vendedor)
            ->get(route('ventas.show', $venta))
            ->assertOk()
            ->assertSee('papel-carta', false);
    }

    public function test_la_preferencia_de_autoimpresion_del_pos_se_guarda_por_usuario(): void
    {
        $this->actingAs($this->vendedor)
            ->postJson('/preferencias/comprobante', ['imprimir_pos' => false])
            ->assertOk();

        $this->assertFalse($this->vendedor->fresh()->pref_imprimir_pos);

        // El checkbox del POS aparece sin marcar con la preferencia guardada.
        $this->actingAs($this->vendedor)
            ->get(route('ventas.create'))
            ->assertOk()
            ->assertDontSee('id="imprimir_comprobante" checked', false);
    }

    public function test_imprimir_comprobante_queda_registrado_en_la_auditoria(): void
    {
        $producto = $this->crearProducto(stock: 5);
        $venta = $this->ventaService()->crearVenta(
            ['estado' => 'Pagado'],
            [['producto_id' => $producto->id, 'cantidad' => 1]],
            $this->vendedor,
            ['monto' => 55.00, 'metodo' => 'Efectivo']
        );

        Log::spy();

        $this->actingAs($this->vendedor)
            ->post('/ventas/'.$venta->id.'/imprimir?origen=pos')
            ->assertOk()
            ->assertJson(['ok' => true]);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(fn (string $mensaje, array $contexto) => $mensaje === 'Comprobante impreso'
                && $contexto['venta_id'] === $venta->id
                && $contexto['origen'] === 'pos'
                && $contexto['usuario_id'] === $this->vendedor->id
            );
    }

    public function test_el_comprobante_solo_autoimprime_con_el_parametro_imprimir(): void
    {
        $producto = $this->crearProducto(stock: 5);
        $cliente = $this->crearCliente();

        $venta = $this->ventaService()->crearVenta(
            ['cliente_id' => $cliente->id, 'estado' => 'Pagado'],
            [['producto_id' => $producto->id, 'cantidad' => 1]],
            $this->vendedor,
            ['monto' => 55.00, 'metodo' => 'Efectivo']
        );

        // Con ?imprimir=1 el script de impresión automática está presente.
        $this->actingAs($this->vendedor)
            ->get(route('ventas.show', ['venta' => $venta, 'imprimir' => 1]))
            ->assertOk()
            ->assertSee('data-auto-imprimir', false);

        // Sin el parámetro no se dispara la impresión automática.
        $this->actingAs($this->vendedor)
            ->get(route('ventas.show', $venta))
            ->assertOk()
            ->assertDontSee('data-auto-imprimir', false);
    }

    private function ventaService(): VentaService
    {
        return app(VentaService::class);
    }
}
