<?php

namespace Tests\Feature;

use App\Jobs\NotificarClientePedido;
use App\Jobs\NotificarTallerPedido;
use App\Mail\PedidoMail;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
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
            'metodo_pago' => 'Efectivo',
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
            'metodo_pago' => 'Efectivo',
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
            'metodo_pago' => 'Efectivo',
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
            'metodo_pago' => 'Efectivo',
        ]);

        $pedido = Pedido::firstOrFail();
        $vendedor = User::factory()->create()->assignRole('Vendedor');

        $this->actingAs($vendedor)->post('/pedidos/'.$pedido->id.'/cancelar')->assertRedirect('/pedidos');

        $this->assertSame('Cancelado', $pedido->fresh()->estado);
        $this->assertCount(0, Venta::all());
        $this->assertSame(10, $producto->fresh()->stock); // sin descuento
    }

    public function test_las_notificaciones_del_pedido_van_a_la_cola(): void
    {
        Queue::fake();
        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Cola de Trabajo',
            'telefono' => '71234567',
            'email' => 'cola@example.com',
            'metodo_pago' => 'Efectivo',
        ]);

        $pedido = Pedido::firstOrFail();

        // El checkout no espera al correo ni al aviso: los encola y sigue.
        Queue::assertPushed(NotificarClientePedido::class, fn (NotificarClientePedido $job) => $job->pedidoId === $pedido->id
            && $job->estado === 'recibido');
        Queue::assertPushed(NotificarTallerPedido::class, fn (NotificarTallerPedido $job) => $job->pedidoId === $pedido->id);
    }

    public function test_confirmar_pedido_encola_el_correo_de_confirmado(): void
    {
        Queue::fake();
        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Confirmo en Cola',
            'telefono' => '71234567',
            'email' => 'confirma-cola@example.com',
            'metodo_pago' => 'Efectivo',
        ]);

        $pedido = Pedido::firstOrFail();
        $vendedor = User::factory()->create()->assignRole('Vendedor');
        $this->actingAs($vendedor)->post('/pedidos/'.$pedido->id.'/confirmar')->assertRedirect();

        $ventaId = $pedido->fresh()->venta_id;

        Queue::assertPushed(NotificarClientePedido::class, fn (NotificarClientePedido $job) => $job->pedidoId === $pedido->id
            && $job->estado === 'confirmado'
            && $job->ventaId === $ventaId);
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
            'metodo_pago' => 'Efectivo',
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
            'metodo_pago' => 'Efectivo',
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
            'metodo_pago' => 'Efectivo',
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
            'metodo_pago' => 'Efectivo',
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
            'metodo_pago' => 'Efectivo',
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
            'metodo_pago' => 'Efectivo',
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
            'metodo_pago' => 'Efectivo',
        ]);

        $pedido = Pedido::firstOrFail();

        $this->post('/pedidos/'.$pedido->id.'/confirmar')->assertRedirect('/login');
        $this->assertSame('Pendiente', $pedido->fresh()->estado);
    }

    // ---------- Pago en línea en el checkout ----------

    public function test_checkout_efectivo_deja_el_pedido_pendiente_de_pago(): void
    {
        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 2]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Efrain Choque',
            'telefono' => '71234567',
            'metodo_pago' => 'Efectivo',
        ])->assertRedirect('/catalogo/pedidos/'.Pedido::first()->id);

        $pedido = Pedido::firstOrFail();

        $this->assertSame('Efectivo', $pedido->metodo_pago);
        $this->assertSame('Pendiente', $pedido->estado_pago);
    }

    public function test_checkout_stripe_en_simulacion_marca_el_pedido_pagado(): void
    {
        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $respuesta = $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Pago en línea',
            'telefono' => '71234567',
            'metodo_pago' => 'Stripe',
        ]);

        $pedido = Pedido::firstOrFail();

        // Sin claves API, el checkout redirige a la simulación.
        $respuesta->assertRedirect('/catalogo/pedidos/'.$pedido->id.'/pago/simular/Stripe');

        $this->get('/catalogo/pedidos/'.$pedido->id.'/pago/simular/Stripe')
            ->assertRedirect('/catalogo/pedidos/'.$pedido->id);

        $pedido->refresh();
        $this->assertSame('Pagado', $pedido->estado_pago);
        $this->assertSame('Stripe', $pedido->metodo_pago);
        $this->assertNotNull($pedido->referencia_pago);
    }

    public function test_confirmar_pedido_pagado_genera_venta_con_pago_completado(): void
    {
        $producto = $this->crearProducto(stock: 8);

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 3]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Compra Pagada',
            'telefono' => '71234567',
            'metodo_pago' => 'Stripe',
        ]);

        $pedido = Pedido::firstOrFail();
        $this->get('/catalogo/pedidos/'.$pedido->id.'/pago/simular/Stripe');

        $vendedor = User::factory()->create()->assignRole('Vendedor');
        $this->actingAs($vendedor)->post('/pedidos/'.$pedido->id.'/confirmar')->assertRedirect();

        $venta = $pedido->fresh()->venta;
        $this->assertNotNull($venta);
        $this->assertSame('Pagado', $venta->estado);
        $this->assertCount(1, $venta->pagos);
        $this->assertSame('Completado', $venta->pagos->first()->estado);
        $this->assertSame('Stripe', $venta->pagos->first()->metodo);
        $this->assertSame('135.00', $venta->pagos->first()->monto); // 3 × Bs 45
    }

    public function test_confirmar_pedido_sin_pago_genera_venta_pendiente_sin_pagos(): void
    {
        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Compra a Crédito',
            'telefono' => '71234567',
            'metodo_pago' => 'Efectivo',
        ]);

        $pedido = Pedido::firstOrFail();
        $vendedor = User::factory()->create()->assignRole('Vendedor');
        $this->actingAs($vendedor)->post('/pedidos/'.$pedido->id.'/confirmar')->assertRedirect();

        $venta = $pedido->fresh()->venta;
        $this->assertSame('Pendiente', $venta->estado);
        $this->assertCount(0, $venta->pagos);
    }

    public function test_metodo_de_pago_invalido_en_checkout_se_rechaza(): void
    {
        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Método raro',
            'telefono' => '71234567',
            'metodo_pago' => 'Tarjeta',
        ])->assertSessionHasErrors('metodo_pago');

        $this->assertCount(0, Pedido::all());
    }

    // ---------- Confirmación/cancelación del cliente por token ----------

    public function test_cliente_confirma_su_pedido_desde_el_correo_con_token(): void
    {
        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Confirmo Mi Pedido',
            'telefono' => '71234567',
            'email' => 'confirmo@example.com',
            'metodo_pago' => 'Efectivo',
        ]);

        $pedido = Pedido::firstOrFail();
        $this->assertNotNull($pedido->token);

        $this->get('/catalogo/pedidos/'.$pedido->id.'/confirmar/'.$pedido->token)
            ->assertRedirect('/catalogo/pedidos/'.$pedido->id.'/estado?telefono=71234567');

        $this->assertNotNull($pedido->fresh()->cliente_confirmado_en);
        $this->assertSame('Pendiente', $pedido->fresh()->estado);
    }

    public function test_cliente_cancela_su_pedido_desde_el_correo_con_token(): void
    {
        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Cancelo Mi Pedido',
            'telefono' => '71234567',
            'email' => 'cancelo@example.com',
            'metodo_pago' => 'Efectivo',
        ]);

        $pedido = Pedido::firstOrFail();

        $this->get('/catalogo/pedidos/'.$pedido->id.'/cancelar/'.$pedido->token)
            ->assertRedirect('/catalogo/pedidos/'.$pedido->id.'/estado?telefono=71234567');

        $this->assertSame('Cancelado', $pedido->fresh()->estado);
        $this->assertSame(10, $producto->fresh()->stock); // sin descuento
    }

    public function test_token_invalido_no_revela_nada_y_no_cambia_el_pedido(): void
    {
        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Token Malo',
            'telefono' => '71234567',
            'metodo_pago' => 'Efectivo',
        ]);

        $pedido = Pedido::firstOrFail();

        $this->get('/catalogo/pedidos/'.$pedido->id.'/confirmar/token-incorrecto')->assertNotFound();
        $this->get('/catalogo/pedidos/'.$pedido->id.'/cancelar/token-incorrecto')->assertNotFound();

        $this->assertSame('Pendiente', $pedido->fresh()->estado);
        $this->assertNull($pedido->fresh()->cliente_confirmado_en);
    }

    public function test_el_taller_no_puede_procesar_un_pedido_cancelado_por_el_cliente(): void
    {
        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Retirado por Cliente',
            'telefono' => '71234567',
            'metodo_pago' => 'Efectivo',
        ]);

        $pedido = Pedido::firstOrFail();
        $this->get('/catalogo/pedidos/'.$pedido->id.'/cancelar/'.$pedido->token);

        $vendedor = User::factory()->create()->assignRole('Vendedor');
        $this->actingAs($vendedor)
            ->from('/pedidos/'.$pedido->id)
            ->post('/pedidos/'.$pedido->id.'/confirmar')
            ->assertRedirect('/pedidos/'.$pedido->id);

        $this->assertSame('Cancelado', $pedido->fresh()->estado);
        $this->assertCount(0, Venta::all());
    }

    // ---------- Notificación al taller (WhatsApp / push) ----------

    public function test_al_recibir_pedido_se_envia_el_aviso_whatsapp_al_taller(): void
    {
        Http::fake();
        config(['services.whatsapp' => [
            'enabled' => true,
            'url' => 'https://webhook.example.com/whatsapp',
            'token' => 'secreto',
            'to' => '+59171234567',
        ]]);

        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 2]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Aviso al Taller',
            'telefono' => '71234567',
            'metodo_pago' => 'Efectivo',
        ]);

        $pedido = Pedido::firstOrFail();

        Http::assertSent(fn ($request) => $request->url() === 'https://webhook.example.com/whatsapp'
            && $request['to'] === '+59171234567'
            && $request['token'] === 'secreto'
            && str_contains($request['text'], "Nuevo pedido #{$pedido->id}")
            && str_contains($request['text'], 'Bs 90.00'));
    }

    public function test_whatsapp_desactivado_deja_el_aviso_en_log(): void
    {
        Log::spy();
        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Aviso por Log',
            'telefono' => '71234567',
            'metodo_pago' => 'Efectivo',
        ]);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(fn (string $mensaje) => str_contains($mensaje, 'pedido recibido del catálogo'));
    }

    public function test_fallo_del_webhook_no_rompe_el_pedido(): void
    {
        Http::fake(['https://webhook.example.com/*' => Http::response('error', 500)]);
        config(['services.whatsapp' => [
            'enabled' => true,
            'url' => 'https://webhook.example.com/whatsapp',
            'token' => 'secreto',
            'to' => '+59171234567',
        ]]);

        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Webhook Caído',
            'telefono' => '71234567',
            'metodo_pago' => 'Efectivo',
        ])->assertRedirect('/catalogo/pedidos/'.Pedido::first()->id);

        $this->assertSame(1, Pedido::count());
    }

    // ---------- Bandeja: paginación y filtro por estado de pago ----------

    public function test_bandeja_filtra_por_estado_de_pago(): void
    {
        // 15 al recibir + 3 pagados en línea (uno con confirmación del cliente).
        for ($i = 1; $i <= 15; $i++) {
            Pedido::create([
                'nombre_cliente' => "Cliente Al Recibir {$i}",
                'telefono' => '71234567',
                'total' => 45.00,
                'estado' => 'Pendiente',
                'metodo_pago' => 'Efectivo',
                'estado_pago' => 'Pendiente',
            ]);
        }

        Pedido::create([
            'nombre_cliente' => 'Cliente Pagado A', 'telefono' => '71234567', 'total' => 90.00,
            'estado' => 'Pendiente', 'metodo_pago' => 'Stripe', 'estado_pago' => 'Pagado',
        ]);
        Pedido::create([
            'nombre_cliente' => 'Cliente Pagado B', 'telefono' => '71234567', 'total' => 135.00,
            'estado' => 'Pendiente', 'metodo_pago' => 'PayPal', 'estado_pago' => 'Pagado',
        ]);
        Pedido::create([
            'nombre_cliente' => 'Cliente Pagado C', 'telefono' => '71234567', 'total' => 45.00,
            'estado' => 'Confirmado', 'metodo_pago' => 'Stripe', 'estado_pago' => 'Pagado',
            'venta_id' => Venta::create([
                'fecha' => now(), 'user_id' => User::factory()->create()->id,
                'total' => 45.00, 'estado' => 'Pagado',
            ])->id,
        ]);

        $vendedor = User::factory()->create()->assignRole('Vendedor');

        // Sin filtros: paginación de 15 (hay 18 pedidos).
        $this->actingAs($vendedor)->get('/pedidos')
            ->assertOk()
            ->assertSee('Cliente Al Recibir 1')
            ->assertSee('Siguiente');

        // Filtro por estado de pago.
        $this->actingAs($vendedor)->get('/pedidos?estado_pago=Pagado')
            ->assertOk()
            ->assertSee('Cliente Pagado A')
            ->assertSee('Cliente Pagado B')
            ->assertSee('Cliente Pagado C')
            ->assertDontSee('Cliente Al Recibir 1');

        $this->actingAs($vendedor)->get('/pedidos?estado_pago=Pendiente')
            ->assertOk()
            ->assertDontSee('Cliente Pagado A');

        // Combinación estado + pago.
        $this->actingAs($vendedor)->get('/pedidos?estado=Confirmado&estado_pago=Pagado')
            ->assertOk()
            ->assertSee('Cliente Pagado C')
            ->assertDontSee('Cliente Pagado B');
    }

    public function test_la_paginacion_de_la_bandeja_mantiene_los_filtros_de_estado_y_pago(): void
    {
        // 17 pedidos Pendiente y pagados en línea: página 1 tiene 15, página 2 tiene 2.
        // created_at se fuerza con forceFill porque no está en $fillable del modelo.
        for ($i = 1; $i <= 17; $i++) {
            $pedido = Pedido::create([
                'nombre_cliente' => "Pagado {$i}",
                'telefono' => '71234567',
                'total' => 45.00,
                'estado' => 'Pendiente',
                'metodo_pago' => 'Stripe',
                'estado_pago' => 'Pagado',
            ]);
            $pedido->forceFill(['created_at' => now()->subMinutes($i)])->save();
        }

        $vendedor = User::factory()->create()->assignRole('Vendedor');

        // Página 1 con ambos filtros: los 15 más recientes y el enlace
        // "Siguiente" conserva estado + pago en la URL.
        $this->actingAs($vendedor)->get('/pedidos?estado=Pendiente&estado_pago=Pagado')
            ->assertOk()
            ->assertSee('Pagado 1')
            ->assertSee('Pagado 15')
            ->assertDontSee('Pagado 16')
            ->assertDontSee('Pagado 17')
            ->assertSee('Siguiente')
            ->assertSee('estado=Pendiente&amp;estado_pago=Pagado&amp;page=2', false);

        // Página 2: el enlace "Anterior" también mantiene ambos filtros.
        $this->actingAs($vendedor)->get('/pedidos?estado=Pendiente&estado_pago=Pagado&page=2')
            ->assertOk()
            ->assertSee('Pagado 16')
            ->assertSee('Pagado 17')
            ->assertDontSee('Pagado 15')
            ->assertSee('Anterior')
            ->assertSee('estado=Pendiente&amp;estado_pago=Pagado&amp;page=1', false);
    }

    // ---------- Detalle de venta con origen del pedido en línea ----------

    public function test_detalle_de_venta_muestra_el_origen_del_pedido_en_linea(): void
    {
        $producto = $this->crearProducto();

        $this->post('/catalogo/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->post('/catalogo/pedidos', [
            'nombre_cliente' => 'Origen Visible',
            'telefono' => '71234567',
            'metodo_pago' => 'Efectivo',
        ]);

        $pedido = Pedido::firstOrFail();
        $vendedor = User::factory()->create()->assignRole('Vendedor');
        $this->actingAs($vendedor)->post('/pedidos/'.$pedido->id.'/confirmar')->assertRedirect();

        $venta = $pedido->fresh()->venta;

        $this->actingAs($vendedor)->get('/ventas/'.$venta->id)
            ->assertOk()
            ->assertSee('Origen: pedido en línea #'.$pedido->id)
            ->assertSee('Ver pedido');
    }
}
