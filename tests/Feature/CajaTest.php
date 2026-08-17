<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\CierreCaja;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CajaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function usuarioConRol(string $rol): User
    {
        return User::factory()->create()->assignRole($rol);
    }

    private function crearVentaDeHoy(User $vendedor, float $total, string $metodo = 'Efectivo'): Venta
    {
        $venta = Venta::create([
            'fecha' => now(),
            'user_id' => $vendedor->id,
            'total' => $total,
            'estado' => 'Pagado',
        ]);

        $venta->pagos()->create([
            'monto' => $total,
            'metodo' => $metodo,
            'estado' => 'Completado',
        ]);

        return $venta;
    }

    public function test_vendedor_puede_ver_historial_y_vista_previa_de_cierre(): void
    {
        $vendedor = $this->usuarioConRol('Vendedor');

        $this->actingAs($vendedor)->get('/caja')->assertOk();
        $this->actingAs($vendedor)->get('/caja/cierre')->assertOk();
    }

    public function test_cerrar_caja_registra_el_arqueo_con_totales_por_metodo(): void
    {
        $vendedor = $this->usuarioConRol('Vendedor');
        $this->crearVentaDeHoy($vendedor, 100.00, 'Efectivo');
        $this->crearVentaDeHoy($vendedor, 45.00, 'Transferencia');

        $this->actingAs($vendedor)->post('/caja/cierre')->assertRedirect();

        $cierre = CierreCaja::where('user_id', $vendedor->id)->first();

        $this->assertNotNull($cierre);
        $this->assertEquals(2, $cierre->cantidad_ventas);
        $this->assertEquals('145.00', $cierre->total_ventas);
        $this->assertEquals('100.00', $cierre->total_efectivo);
        $this->assertEquals('45.00', $cierre->total_transferencia);
        $this->assertSame(today()->toDateString(), $cierre->fecha_cierre->toDateString());
    }

    public function test_no_se_puede_cerrar_la_caja_dos_veces_el_mismo_dia(): void
    {
        $vendedor = $this->usuarioConRol('Vendedor');

        $this->actingAs($vendedor)->post('/caja/cierre')->assertRedirect();
        $this->actingAs($vendedor)->from('/caja/cierre')->post('/caja/cierre')->assertRedirect('/caja/cierre');

        $this->assertCount(1, CierreCaja::all());
    }

    public function test_admin_ve_los_cierres_de_todos_y_vendedor_solo_los_suyos(): void
    {
        $admin = $this->usuarioConRol('Admin');
        $vendedorUno = User::factory()->create(['name' => 'Vendedor Uno'])->assignRole('Vendedor');
        $vendedorDos = User::factory()->create(['name' => 'Vendedor Dos'])->assignRole('Vendedor');

        $this->crearVentaDeHoy($vendedorUno, 50.00);
        $this->crearVentaDeHoy($vendedorDos, 70.00);

        $this->actingAs($vendedorUno)->post('/caja/cierre')->assertRedirect();
        $this->actingAs($vendedorDos)->post('/caja/cierre')->assertRedirect();

        $this->actingAs($admin)->get('/caja')
            ->assertOk()
            ->assertSee('Vendedor Uno')
            ->assertSee('Vendedor Dos');

        $this->actingAs($vendedorUno)->get('/caja')
            ->assertOk()
            ->assertSee('Vendedor Uno')
            ->assertDontSee('Vendedor Dos');
    }

    public function test_rol_cliente_no_accede_al_cierre_de_caja(): void
    {
        $cliente = $this->usuarioConRol('Cliente');

        $this->actingAs($cliente)->get('/caja')->assertForbidden();
        $this->actingAs($cliente)->post('/caja/cierre')->assertForbidden();
    }

    public function test_admin_exporta_el_cierre_a_pdf(): void
    {
        $admin = $this->usuarioConRol('Admin');
        $vendedor = $this->usuarioConRol('Vendedor');
        $this->crearVentaDeHoy($vendedor, 100.00, 'Efectivo');
        $this->crearVentaDeHoy($vendedor, 45.00, 'Transferencia');

        $this->actingAs($vendedor)->post('/caja/cierre')->assertRedirect();
        $cierre = CierreCaja::firstOrFail();

        $this->actingAs($admin)->get('/caja/'.$cierre->id.'/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_vendedor_no_exporta_un_cierre_ajeno(): void
    {
        $vendedorA = $this->usuarioConRol('Vendedor');
        $vendedorB = $this->usuarioConRol('Vendedor');
        $this->crearVentaDeHoy($vendedorA, 50.00);

        $this->actingAs($vendedorA)->post('/caja/cierre')->assertRedirect();
        $cierre = CierreCaja::firstOrFail();

        $this->actingAs($vendedorB)->get('/caja/'.$cierre->id.'/pdf')->assertForbidden();
    }
}
