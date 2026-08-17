<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Devolucion;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\DevolucionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevolucionTest extends TestCase
{
    use RefreshDatabase;

    private User $vendedor;
    private User $admin;
    private Venta $venta;
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
            'precio_unitario' => 60.00,
            'stock' => 8,
            'umbral_alerta' => 2,
        ]);

        $cliente = Cliente::create(['nombre' => 'Cliente devolución']);
        $this->venta = Venta::create([
            'fecha' => now(),
            'user_id' => $this->vendedor->id,
            'cliente_id' => $cliente->id,
            'total' => 120.00,
            'estado' => 'Pagado',
        ]);
        $this->venta->detalles()->create([
            'producto_id' => $this->producto->id,
            'cantidad' => 2,
            'precio_unitario' => 60.00,
        ]);
    }

    public function test_vendedor_puede_registrar_una_devolucion(): void
    {
        $response = $this->actingAs($this->vendedor)
            ->post(route('devoluciones.store', $this->venta), [
                'producto_id' => $this->producto->id,
                'cantidad' => 1,
                'motivo' => 'Producto defectuoso',
            ]);

        $response->assertRedirect(route('devoluciones.index'));

        $devolucion = Devolucion::first();
        $this->assertNotNull($devolucion);
        $this->assertEquals('Pendiente', $devolucion->estado);
        $this->assertEquals('Producto defectuoso', $devolucion->motivo);
        $this->assertEquals($this->vendedor->id, $devolucion->user_id);
        $this->assertEquals(8, $this->producto->fresh()->stock); // aún sin reponer
    }

    public function test_no_se_puede_devolver_mas_de_lo_vendido(): void
    {
        $response = $this->actingAs($this->vendedor)
            ->post(route('devoluciones.store', $this->venta), [
                'producto_id' => $this->producto->id,
                'cantidad' => 3,
                'motivo' => 'Error',
            ]);

        $response->assertSessionHasErrors('cantidad');
        $this->assertDatabaseCount('devoluciones', 0);
    }

    public function test_no_se_puede_devolver_producto_que_no_es_de_la_venta(): void
    {
        $otro = Producto::create([
            'categoria_id' => $this->producto->categoria_id,
            'nombre' => 'Otro producto',
            'precio_unitario' => 10.00,
            'stock' => 5,
            'umbral_alerta' => 1,
        ]);

        $response = $this->actingAs($this->vendedor)
            ->post(route('devoluciones.store', $this->venta), [
                'producto_id' => $otro->id,
                'cantidad' => 1,
                'motivo' => 'Error',
            ]);

        $response->assertSessionHasErrors('producto_id');
    }

    public function test_aprobar_devolucion_repone_stock_y_registra_reembolso(): void
    {
        $devolucion = app(DevolucionService::class)->crear($this->venta, [
            'producto_id' => $this->producto->id,
            'cantidad' => 1,
            'motivo' => 'Taller equivocado',
        ], $this->vendedor);

        $response = $this->actingAs($this->admin)
            ->post(route('devoluciones.aprobar', $devolucion));

        $response->assertRedirect(route('devoluciones.index'));

        $devolucion->refresh();
        $this->assertEquals('Aprobada', $devolucion->estado);
        $this->assertEquals('60.00', $devolucion->monto_reembolso); // 1 × precio original

        // Stock repuesto: 8 + 1
        $this->assertEquals(9, $this->producto->fresh()->stock);

        // Movimiento de devolución registrado
        $movimiento = MovimientoStock::where('producto_id', $this->producto->id)
            ->where('tipo', 'devolucion')
            ->first();
        $this->assertNotNull($movimiento);
        $this->assertEquals(1, $movimiento->cantidad);
        $this->assertEquals(9, $movimiento->stock_resultante);
    }

    public function test_aprobar_permite_ajustar_el_monto_de_reembolso(): void
    {
        $devolucion = app(DevolucionService::class)->crear($this->venta, [
            'producto_id' => $this->producto->id,
            'cantidad' => 2,
            'motivo' => 'Reembolso parcial',
        ], $this->vendedor);

        $this->actingAs($this->admin)
            ->post(route('devoluciones.aprobar', $devolucion), ['monto_reembolso' => 100.00]);

        $this->assertEquals('Aprobada', $devolucion->fresh()->estado);
        $this->assertEquals('100.00', $devolucion->fresh()->monto_reembolso);
    }

    public function test_rechazar_devolucion_no_modifica_el_stock(): void
    {
        $devolucion = app(DevolucionService::class)->crear($this->venta, [
            'producto_id' => $this->producto->id,
            'cantidad' => 1,
            'motivo' => 'Sin embalaje',
        ], $this->vendedor);

        $this->actingAs($this->admin)
            ->post(route('devoluciones.rechazar', $devolucion), ['motivo_rechazo' => 'Fuera de plazo']);

        $devolucion->refresh();
        $this->assertEquals('Rechazada', $devolucion->estado);
        $this->assertStringContainsString('Fuera de plazo', $devolucion->motivo);
        $this->assertEquals(8, $this->producto->fresh()->stock);
    }

    public function test_no_se_puede_procesar_una_devolucion_dos_veces(): void
    {
        $devolucion = app(DevolucionService::class)->crear($this->venta, [
            'producto_id' => $this->producto->id,
            'cantidad' => 1,
            'motivo' => 'Prueba',
        ], $this->vendedor);

        $this->actingAs($this->admin)->post(route('devoluciones.aprobar', $devolucion));

        $this->expectException(\DomainException::class);
        app(DevolucionService::class)->aprobar($devolucion->fresh());
    }

    public function test_vendedor_puede_ver_pero_no_aprobar_devoluciones(): void
    {
        $devolucion = app(DevolucionService::class)->crear($this->venta, [
            'producto_id' => $this->producto->id,
            'cantidad' => 1,
            'motivo' => 'Prueba de acceso',
        ], $this->vendedor);

        $this->actingAs($this->vendedor)->get('/devoluciones')->assertOk();
        $this->actingAs($this->vendedor)->post(route('devoluciones.aprobar', $devolucion))->assertForbidden();
    }
}
