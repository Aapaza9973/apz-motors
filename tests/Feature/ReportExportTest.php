<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $categoria = Categoria::create(['nombre' => 'Iluminación', 'tipo' => 'Accesorio']);
        Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Bombillo LED',
            'precio_unitario' => 70.00,
            'stock' => 3,
            'umbral_alerta' => 5,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('Admin');
    }

    public function test_exporta_pdf_de_ventas(): void
    {
        $response = $this->actingAs($this->admin())->get('/reportes/ventas/exportar/pdf');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_exporta_csv_de_ventas(): void
    {
        $response = $this->actingAs($this->admin())->get('/reportes/ventas/exportar/csv');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('reporte-ventas', $response->headers->get('Content-Disposition'));

        $contenido = $this->contenidoStream($response);
        $this->assertStringContainsString('N.º', $contenido);
    }

    public function test_exporta_pdf_de_inventario(): void
    {
        $response = $this->actingAs($this->admin())->get('/reportes/inventario/exportar/pdf');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_exporta_csv_de_inventario_con_filtro_bajo_stock(): void
    {
        $response = $this->actingAs($this->admin())->get('/reportes/inventario/exportar/csv?bajo=1');

        $response->assertOk();
        $contenido = $this->contenidoStream($response);
        $this->assertStringContainsString('Bombillo LED', $contenido);
        $this->assertStringContainsString('Bajo stock', $contenido);
        $this->assertStringContainsString('Producto', $contenido);
    }

    /**
     * Ejecuta el stream de una StreamedResponse y devuelve lo emitido.
     */
    private function contenidoStream($response): string
    {
        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }

    public function test_vendedor_puede_exportar_reportes(): void
    {
        $vendedor = User::factory()->create()->assignRole('Vendedor');
        $this->actingAs($vendedor)->get('/reportes/ventas/exportar/csv')->assertOk();
    }

    public function test_rol_cliente_no_puede_exportar_reportes(): void
    {
        $cliente = User::factory()->create()->assignRole('Cliente');
        $this->actingAs($cliente)->get('/reportes/ventas/exportar/pdf')->assertForbidden();
    }
}
