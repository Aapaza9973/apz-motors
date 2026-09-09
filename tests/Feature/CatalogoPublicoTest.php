<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Support\Carrito;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoPublicoTest extends TestCase
{
    use RefreshDatabase;

    private function crearProducto(string $nombre, string $categoriaNombre, float $precio, int $stock, string $descripcion = ''): Producto
    {
        $categoria = Categoria::firstOrCreate(
            ['nombre' => $categoriaNombre],
            ['tipo' => 'Repuesto']
        );

        return Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'precio_unitario' => $precio,
            'stock' => $stock,
            'umbral_alerta' => 2,
            'descripcion' => $descripcion,
        ]);
    }

    public function test_catalogo_es_publico_sin_autenticacion(): void
    {
        $this->get('/catalogo')->assertOk()->assertSee('Catálogo de repuestos');
    }

    public function test_catalogo_muestra_productos_con_precio(): void
    {
        $this->crearProducto('Filtro de aire de alto flujo', 'Repuestos de Motor', 45.00, 30);

        $this->get('/catalogo')
            ->assertOk()
            ->assertSee('Filtro de aire de alto flujo')
            ->assertSee('Bs 45.00');
    }

    public function test_catalogo_filtra_por_categoria(): void
    {
        $this->crearProducto('Disco de freno 260mm', 'Frenos', 140.00, 4);
        $this->crearProducto('Casco integral talla M', 'Cascos y Seguridad', 450.00, 8);

        $frenos = Categoria::where('nombre', 'Frenos')->first();

        $this->get('/catalogo?categoria='.$frenos->id)
            ->assertOk()
            ->assertSee('Disco de freno 260mm')
            ->assertDontSee('Casco integral talla M');
    }

    public function test_catalogo_muestra_estado_vacio_con_voz_de_taller(): void
    {
        $this->get('/catalogo?q=inexistente')
            ->assertOk()
            ->assertSee('Sin repuestos para este filtro')
            ->assertSee('Limpiar filtros');
    }

    public function test_dashboard_interno_sigue_protegido(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_ficha_de_producto_es_publica_y_muestra_detalle_y_cta_de_pedido(): void
    {
        $producto = $this->crearProducto(
            'Bombillo LED H4',
            'Eléctricos',
            70.00,
            8,
            'Luz blanca de alta intensidad para uso diario y nocturno.'
        );

        $this->get("/catalogo/productos/{$producto->id}")
            ->assertOk()
            ->assertSee('Bombillo LED H4')
            ->assertSee('Bs 70.00')
            ->assertSee('Luz blanca de alta intensidad para uso diario y nocturno.')
            ->assertSee('Ficha técnica')
            ->assertSee('Agregar al carrito')
            ->assertSee('Pedido online');
    }

    public function test_ficha_de_producto_inexistente_devuelve_404(): void
    {
        $this->get('/catalogo/productos/99999')->assertNotFound();
    }

    public function test_catalogo_ordena_por_precio_ascendente(): void
    {
        $this->crearProducto('Repuesto caro', 'Repuestos', 450.00, 5);
        $this->crearProducto('Repuesto barato', 'Repuestos', 45.00, 5);
        $this->crearProducto('Repuesto medio', 'Repuestos', 140.00, 5);

        $contenido = $this->get('/catalogo?orden=precio-asc')->assertOk()->getContent();

        $this->assertTrue(
            strpos($contenido, 'Repuesto barato') < strpos($contenido, 'Repuesto medio')
            && strpos($contenido, 'Repuesto medio') < strpos($contenido, 'Repuesto caro'),
            'El catálogo debería ordenar de menor a mayor precio.'
        );
    }

    public function test_catalogo_ordena_por_precio_descendente(): void
    {
        $this->crearProducto('Repuesto caro', 'Repuestos', 450.00, 5);
        $this->crearProducto('Repuesto barato', 'Repuestos', 45.00, 5);

        $contenido = $this->get('/catalogo?orden=precio-desc')->assertOk()->getContent();

        $this->assertTrue(
            strpos($contenido, 'Repuesto caro') < strpos($contenido, 'Repuesto barato'),
            'El catálogo debería ordenar de mayor a menor precio.'
        );
    }

    public function test_catalogo_muestra_cta_de_pedido_y_responde_con_carrito_lleno(): void
    {
        $this->crearProducto('Filtro de aire de alto flujo', 'Repuestos de Motor', 45.00, 30);

        $this->get('/catalogo')
            ->assertOk()
            ->assertSee('Pedí online, sin cuenta')
            ->assertSee('Armar mi pedido');

        $producto = Producto::where('nombre', 'Filtro de aire de alto flujo')->firstOrFail();

        $this->withSession([Carrito::CLAVE => [$producto->id => 2]])
            ->get('/catalogo')
            ->assertOk()
            ->assertSee('2 producto(s)')
            ->assertSee('Finalizar mi pedido');
    }
}
