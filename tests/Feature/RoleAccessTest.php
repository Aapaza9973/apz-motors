<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
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

    public function test_usuario_no_autenticado_es_redirigido_al_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/productos')->assertRedirect('/login');
    }

    public function test_vendedor_puede_ver_productos_pero_no_crearlos(): void
    {
        $vendedor = $this->usuarioConRol('Vendedor');

        $this->actingAs($vendedor)->get('/productos')->assertOk();
        $this->actingAs($vendedor)->get('/productos/crear')->assertForbidden();
        $this->actingAs($vendedor)->post('/productos', [
            'categoria_id' => 1,
            'nombre' => 'Producto no permitido',
            'precio_unitario' => 10,
            'stock' => 1,
            'umbral_alerta' => 1,
        ])->assertForbidden();
    }

    public function test_vendedor_puede_acceder_al_punto_de_venta(): void
    {
        $vendedor = $this->usuarioConRol('Vendedor');
        $this->actingAs($vendedor)->get('/ventas/crear')->assertOk();
        $this->actingAs($vendedor)->get('/ventas')->assertOk();
    }

    public function test_encargado_de_inventario_no_puede_registrar_ventas(): void
    {
        $inventario = $this->usuarioConRol('Inventario');

        $this->actingAs($inventario)->get('/ventas/crear')->assertForbidden();
        $this->actingAs($inventario)->post('/ventas', ['items' => []])->assertForbidden();
    }

    public function test_encargado_de_inventario_puede_gestionar_productos(): void
    {
        $inventario = $this->usuarioConRol('Inventario');
        $categoria = Categoria::create(['nombre' => 'Accesorios', 'tipo' => 'Accesorio']);

        $this->actingAs($inventario)->get('/productos/crear')->assertOk();

        $response = $this->actingAs($inventario)->post('/productos', [
            'categoria_id' => $categoria->id,
            'nombre' => 'Espejo retrovisor',
            'precio_unitario' => 35.00,
            'stock' => 10,
            'umbral_alerta' => 3,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('productos', ['nombre' => 'Espejo retrovisor']);
    }

    public function test_admin_puede_gestionar_usuarios(): void
    {
        $admin = $this->usuarioConRol('Admin');

        $this->actingAs($admin)->get('/usuarios')->assertOk();
        $this->actingAs($admin)->get('/usuarios/crear')->assertOk();
    }

    public function test_vendedor_no_puede_gestionar_usuarios(): void
    {
        $vendedor = $this->usuarioConRol('Vendedor');
        $this->actingAs($vendedor)->get('/usuarios')->assertForbidden();
    }

    public function test_rol_cliente_sin_acceso_a_modulos_internos(): void
    {
        $cliente = $this->usuarioConRol('Cliente');

        $this->actingAs($cliente)->get('/ventas')->assertForbidden();
        $this->actingAs($cliente)->get('/reportes/ventas')->assertForbidden();
    }

    public function test_producto_cuenta_con_stock_y_umbral_iniciales(): void
    {
        $admin = $this->usuarioConRol('Admin');
        $categoria = Categoria::create(['nombre' => 'Iluminación', 'tipo' => 'Accesorio']);

        $this->actingAs($admin)->post('/productos', [
            'categoria_id' => $categoria->id,
            'nombre' => 'Bombillo LED',
            'precio_unitario' => 70.00,
            'stock' => 25,
            'umbral_alerta' => 6,
        ]);

        $producto = Producto::where('nombre', 'Bombillo LED')->first();
        $this->assertEquals(25, $producto->stock);
        $this->assertEquals(6, $producto->umbral_alerta);
        $this->assertEquals(1, $producto->movimientos()->count());
        $this->assertEquals('entrada', $producto->movimientos()->first()->tipo);
    }
}
