<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\PaymentService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $vendedor;
    private Venta $venta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->vendedor = User::factory()->create()->assignRole('Vendedor');

        $categoria = Categoria::create(['nombre' => 'Repuestos', 'tipo' => 'Repuesto']);
        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Bujía NGK',
            'precio_unitario' => 25.00,
            'stock' => 10,
            'umbral_alerta' => 2,
        ]);
        $cliente = Cliente::create(['nombre' => 'Cliente de pago']);

        $this->venta = Venta::create([
            'fecha' => now(),
            'user_id' => $this->vendedor->id,
            'cliente_id' => $cliente->id,
            'total' => 50.00,
            'estado' => 'Pendiente',
        ]);
        $this->venta->detalles()->create([
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => 25.00,
        ]);
    }

    public function test_flujo_simulado_marca_la_venta_como_pagada(): void
    {
        $this->assertTrue(app(PaymentService::class)->usaSimulacion('Stripe'));

        $response = $this->actingAs($this->vendedor)
            ->get(route('pagos.simular', ['venta' => $this->venta, 'metodo' => 'Stripe']));

        $response->assertRedirect(route('ventas.show', $this->venta));

        $venta = $this->venta->fresh();
        $this->assertEquals('Pagado', $venta->estado);

        $pago = Pago::where('venta_id', $venta->id)->first();
        $this->assertNotNull($pago);
        $this->assertEquals('Stripe', $pago->metodo);
        $this->assertEquals('50.00', $pago->monto);
        $this->assertEquals('Completado', $pago->estado);
        $this->assertStringStartsWith('SIM-', $pago->referencia);
    }

    public function test_flujo_simulado_con_paypal(): void
    {
        $this->actingAs($this->vendedor)
            ->get(route('pagos.simular', ['venta' => $this->venta, 'metodo' => 'PayPal']));

        $this->assertEquals('Pagado', $this->venta->fresh()->estado);
        $this->assertEquals('PayPal', Pago::where('venta_id', $this->venta->id)->first()->metodo);
    }

    public function test_confirmar_pago_es_idempotente(): void
    {
        $service = app(PaymentService::class);

        $service->confirmar($this->venta, 'Stripe', 'REF-1');
        $service->confirmar($this->venta, 'Stripe', 'REF-1');
        $service->confirmar($this->venta, 'PayPal', 'REF-2');

        $this->assertEquals('Pagado', $this->venta->fresh()->estado);
        $this->assertEquals(2, Pago::where('venta_id', $this->venta->id)->where('estado', 'Completado')->count());
    }

    public function test_no_se_puede_cobrar_una_venta_cancelada(): void
    {
        $this->venta->update(['estado' => 'Cancelada']);

        $this->expectException(\DomainException::class);

        app(PaymentService::class)->confirmar($this->venta, 'Stripe');
    }

    public function test_boton_de_pago_exige_metodo_valido(): void
    {
        $response = $this->actingAs($this->vendedor)
            ->post(route('pagos.iniciar', $this->venta), ['metodo' => 'Bitcoin']);

        $response->assertSessionHasErrors('metodo');
    }

    public function test_flujo_completo_iniciar_y_simular(): void
    {
        $response = $this->actingAs($this->vendedor)
            ->post(route('pagos.iniciar', $this->venta), ['metodo' => 'Stripe']);

        $response->assertRedirect();
        $url = $response->headers->get('Location');
        $this->assertStringContainsString('pagos/'.$this->venta->id.'/simular/Stripe', $url);

        $this->actingAs($this->vendedor)->get($url);

        $this->assertEquals('Pagado', $this->venta->fresh()->estado);
        $this->assertDatabaseHas('pagos', ['venta_id' => $this->venta->id, 'metodo' => 'Stripe']);
    }

    public function test_venta_ya_pagada_no_inicia_nuevo_cobro(): void
    {
        $this->venta->update(['estado' => 'Pagado']);

        $response = $this->actingAs($this->vendedor)
            ->post(route('pagos.iniciar', $this->venta), ['metodo' => 'Stripe']);

        $response->assertRedirect();
        $this->assertEquals('Pagado', $this->venta->fresh()->estado);
    }
}
