<?php

namespace Tests\Feature;

use App\Mail\PedidoMail;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PedidoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function crearProducto(string $nombre = 'Filtro de aire', int $stock = 10, float $precio = 45.00): Producto
    {
        $categoria = Categoria::create(['nombre' => 'Repuestos de Motor', 'tipo' => 'Repuesto']);

        return Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'precio_unitario' => $precio,
            'stock' => $stock,
            'umbral_alerta' => 2,
        ]);
    }

    public function test_cliente_puede_armar_carrito_y_hacer_un_pedido(): void
    {
        $producto = $this->crearProducto(stock: 10);

        $this->get('/catalogo')->assertOk()->assertSee('Agregar al carrito');

        // Agregar dos unidades al carrito.
        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 2])
            ->assertRedirect();

        $this->assertSame([$producto->id => 2], session('carrito'));

        $this->get('/catalogo/carrito')
            ->assertOk()
            ->assertSee($producto->nombre)
            ->assertSee('Bs 90.00');

        // Checkout con los datos del cliente.
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Juan Pérez',
            'telefono' => '71234567',
            'email' => 'juan@example.com',
            'direccion' => 'Av. 6 de Agosto',
            'nota' => 'Para una GN 125',
        ])->assertRedirect();

        $pedido = Pedido::first();

        $this->assertNotNull($pedido);
        $this->assertSame('Pendiente', $pedido->estado);
        $this->assertSame('90.00', $pedido->total);
        $this->assertSame('Juan Pérez', $pedido->nombre_cliente);
        $this->assertCount(1, $pedido->items);
        $this->assertSame(2, $pedido->items->first()->cantidad);
        $this->assertNull(session('carrito'));

        // Página de confirmación pública.
        $this->get('/catalogo/pedidos/'.$pedido->id)
            ->assertOk()
            ->assertSee('Gracias, Juan Pérez')
            ->assertSee('Bs 90.00');
    }

    public function test_no_se_puede_agregar_un_producto_agotado(): void
    {
        $producto = $this->crearProducto('Casco integral', stock: 0);

        $this->from('/catalogo')
            ->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1])
            ->assertRedirect('/catalogo');

        $this->assertNull(session('carrito'));
    }

    public function test_pedido_con_stock_insuficiente_se_rechaza_al_confirmar(): void
    {
        $producto = $this->crearProducto(stock: 3);

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 2]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'María Rojas',
            'telefono' => '71234567',
        ]);

        $pedido = Pedido::firstOrFail();

        // El stock bajó a 1 antes de que el vendedor confirme.
        $producto->update(['stock' => 1]);

        $vendedor = User::factory()->create()->assignRole('Vendedor');

        $this->actingAs($vendedor)
            ->from('/pedidos/'.$pedido->id)
            ->post('/pedidos/'.$pedido->id.'/confirmar')
            ->assertRedirect('/pedidos/'.$pedido->id);

        $this->assertSame('Pendiente', $pedido->fresh()->estado);
        $this->assertCount(0, Venta::all());
        $this->assertSame(1, $producto->fresh()->stock);
    }

    public function test_vendedor_ve_la_bandeja_y_confirma_generando_venta(): void
    {
        $producto = $this->crearProducto(stock: 8);

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 3]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Carlos Mamani',
            'telefono' => '71234567',
        ]);

        $pedido = Pedido::firstOrFail();

        $vendedor = User::factory()->create(['name' => 'Vendedor de turno'])->assignRole('Vendedor');

        $this->actingAs($vendedor)->get('/pedidos')
            ->assertOk()
            ->assertSee('Carlos Mamani')
            ->assertSee('Bs 135.00');

        $this->actingAs($vendedor)->post('/pedidos/'.$pedido->id.'/confirmar')->assertRedirect();

        $pedido->refresh();

        $this->assertSame('Confirmado', $pedido->estado);
        $this->assertSame($vendedor->id, $pedido->user_id);
        $this->assertNotNull($pedido->venta_id);
        $this->assertSame(5, $producto->fresh()->stock); // 8 - 3
        $this->assertSame('Pendiente', $pedido->venta->estado);

        // Los datos del pedido quedaron registrados como cliente del sistema.
        $cliente = Cliente::where('nombre', 'Carlos Mamani')->first();
        $this->assertNotNull($cliente);
        $this->assertSame($cliente->id, $pedido->venta->cliente_id);

        // El detalle del pedido enlaza a la venta.
        $this->actingAs($vendedor)->get('/pedidos/'.$pedido->id)
            ->assertOk()
            ->assertSee('venta #'.$pedido->venta->id);
    }

    public function test_cancelar_pedido_desde_la_bandeja(): void
    {
        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Ana Condori',
            'telefono' => '71234567',
        ]);

        $pedido = Pedido::firstOrFail();
        $vendedor = User::factory()->create()->assignRole('Vendedor');

        $this->actingAs($vendedor)->post('/pedidos/'.$pedido->id.'/cancelar')->assertRedirect('/pedidos');

        $this->assertSame('Cancelado', $pedido->fresh()->estado);
        $this->assertCount(0, Venta::all());
        $this->assertSame(10, $producto->fresh()->stock); // sin descuento
    }

    public function test_al_hacer_pedido_se_envia_correo_de_recibido(): void
    {
        Mail::fake();
        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Laura Rojas',
            'telefono' => '71234567',
            'email' => 'laura@example.com',
        ]);

        $pedido = Pedido::firstOrFail();

        Mail::assertSent(PedidoMail::class, fn (PedidoMail $mail) => $mail->hasTo('laura@example.com')
            && $mail->estado === 'recibido'
            && $mail->pedido->id === $pedido->id);
    }

    public function test_confirmar_envia_correo_al_cliente_con_numero_de_venta(): void
    {
        Mail::fake();
        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Laura Rojas',
            'telefono' => '71234567',
            'email' => 'laura@example.com',
        ]);

        $pedido = Pedido::firstOrFail();
        $vendedor = User::factory()->create()->assignRole('Vendedor');
        $this->actingAs($vendedor)->post('/pedidos/'.$pedido->id.'/confirmar')->assertRedirect();

        Mail::assertSent(PedidoMail::class, fn (PedidoMail $mail) => $mail->estado === 'confirmado'
            && $mail->ventaId === $pedido->fresh()->venta_id);
    }

    public function test_cancelar_envia_correo_de_cancelacion(): void
    {
        Mail::fake();
        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Laura Rojas',
            'telefono' => '71234567',
            'email' => 'laura@example.com',
        ]);

        $pedido = Pedido::firstOrFail();
        $vendedor = User::factory()->create()->assignRole('Vendedor');
        $this->actingAs($vendedor)->post('/pedidos/'.$pedido->id.'/cancelar')->assertRedirect('/pedidos');

        Mail::assertSent(PedidoMail::class, fn (PedidoMail $mail) => $mail->estado === 'cancelado');
    }

    public function test_sin_email_no_se_envia_correo_de_pedido(): void
    {
        Mail::fake();
        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Cliente Sin Correo',
            'telefono' => '71234567',
        ]);

        Mail::assertNothingSent();
    }

    public function test_consulta_publica_de_estado_por_numero_y_telefono(): void
    {
        $producto = $this->crearProducto();
        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Pedro Consulta',
            'telefono' => '71234567',
        ]);

        $pedido = Pedido::firstOrFail();

        $this->get('/catalogo/consultar')->assertOk()->assertSee('Consultar mi pedido');

        $this->post('/catalogo/consultar', ['pedido_id' => $pedido->id, 'telefono' => '71234567'])
            ->assertRedirect('/catalogo/pedidos/'.$pedido->id.'/estado?telefono=71234567');

        $this->get('/catalogo/pedidos/'.$pedido->id.'/estado?telefono=71234567')
            ->assertOk()
            ->assertSee('En el taller')
            ->assertSee('Pedido #'.$pedido->id);
    }

    public function test_consulta_con_telefono_incorrecto_no_revela_el_pedido(): void
    {
        $producto = $this->crearProducto();
        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Pedro Consulta',
            'telefono' => '71234567',
        ]);

        $pedido = Pedido::firstOrFail();

        $this->post('/catalogo/consultar', ['pedido_id' => $pedido->id, 'telefono' => '99999999'])
            ->assertSessionHasErrors('pedido');

        $this->get('/catalogo/pedidos/'.$pedido->id.'/estado?telefono=99999999')->assertNotFound();
    }

    public function test_bandeja_interna_exige_autenticacion_y_permiso(): void
    {
        $this->get('/pedidos')->assertRedirect('/login');

        $cliente = User::factory()->create()->assignRole('Cliente');
        $this->actingAs($cliente)->get('/pedidos')->assertForbidden();

        $inventario = User::factory()->create()->assignRole('Inventario');
        $this->actingAs($inventario)->get('/pedidos')->assertForbidden();
    }

    public function test_un_visitante_no_puede_confirmar_pedidos(): void
    {
        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Pedro Quispe',
            'telefono' => '71234567',
        ]);

        $pedido = Pedido::firstOrFail();

        $this->post('/pedidos/'.$pedido->id.'/confirmar')->assertRedirect('/login');
        $this->assertSame('Pendiente', $pedido->fresh()->estado);
    }
}
