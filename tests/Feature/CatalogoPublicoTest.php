<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoPublicoTest extends TestCase
{
    use RefreshDatabase;

    private function crearProducto(string $nombre, string $categoriaNombre, float $precio, int $stock): Producto
    {
        $categoria = Categoria::create(['nombre' => $categoriaNombre, 'tipo' => 'Repuesto']);

        return Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'precio_unitario' => $precio,
            'stock' => $stock,
            'umbral_alerta' => 2,
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
}
