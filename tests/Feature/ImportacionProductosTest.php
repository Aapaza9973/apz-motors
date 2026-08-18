<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use App\Services\ImportacionProductosService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ImportacionProductosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function csvConBOM(string $contenido): string
    {
        return "\xEF\xBB\xBF".$contenido;
    }

    public function test_analizar_valida_filas_y_reporta_errores(): void
    {
        $csv = $this->csvConBOM(implode("\n", [
            'nombre;categoria;precio_unitario;stock;umbral_alerta;costo;descripcion;tipo',
            'Filtro de aire GN125;Repuestos de Motor;45.00;10;5;25.00;Filtro de aire;Repuesto',
            'Casco sin precio;Cascos; ;5;2;;;',
            'Cadena 428H;Transmisión;95.00;0;3;;;',
        ]));

        $resultado = app(ImportacionProductosService::class)->analizar($csv);

        $this->assertCount(3, $resultado['filas']);
        $this->assertEmpty($resultado['filas'][0]['errores']);
        $this->assertNotEmpty($resultado['filas'][1]['errores']); // precio vacío
        $this->assertStringContainsString('precio', $resultado['filas'][1]['errores'][0]);
        $this->assertEmpty($resultado['filas'][2]['errores']);
        $this->assertSame(4, $resultado['filas'][2]['numero']); // fila real del archivo
        // Las filas válidas indican la acción prevista (crear/actualizar por nombre).
        $this->assertSame('crear', $resultado['filas'][0]['accion']);
        $this->assertNull($resultado['filas'][1]['accion']); // fila con errores
        $this->assertSame('crear', $resultado['filas'][2]['accion']);
    }

    public function test_analizar_rechaza_archivo_sin_encabezados(): void
    {
        $this->expectException(ValidationException::class);

        app(ImportacionProductosService::class)->analizar("Filtro;Motor;45;10\nOtro;X;10;1");
    }

    public function test_importar_crea_productos_categorias_y_movimientos(): void
    {
        $admin = User::factory()->create()->assignRole('Admin');

        $filas = [
            ['numero' => 2, 'datos' => ['nombre' => 'Filtro de aire GN125', 'categoria' => 'Repuestos de Motor', 'precio_unitario' => '45.00', 'stock' => '10', 'umbral_alerta' => '5', 'costo' => '25.00', 'descripcion' => 'Filtro de alto flujo', 'tipo' => 'Repuesto'], 'errores' => []],
            ['numero' => 3, 'datos' => ['nombre' => 'Casco integral M', 'categoria' => 'Cascos y Seguridad', 'precio_unitario' => '450.00', 'stock' => '4', 'umbral_alerta' => '2', 'costo' => '300.00', 'descripcion' => null, 'tipo' => 'Accesorio'], 'errores' => []],
            ['numero' => 4, 'datos' => ['nombre' => 'Mal producto', 'categoria' => 'X', 'precio_unitario' => '', 'stock' => '1', 'umbral_alerta' => '0', 'costo' => null, 'descripcion' => null, 'tipo' => null], 'errores' => ['precio inválido (mayor a 0)']],
        ];

        $resumen = app(ImportacionProductosService::class)->importar($filas, $admin);

        $this->assertSame(2, $resumen['creados']);
        $this->assertSame(0, $resumen['actualizados']);
        $this->assertSame(1, $resumen['errores']);
        $this->assertMatchesRegularExpression('/^IMP-\d{8}-\d{6}$/', $resumen['lote']);

        $this->assertDatabaseHas('productos', ['nombre' => 'Filtro de aire GN125', 'stock' => 10, 'precio_unitario' => '45.00']);
        $this->assertDatabaseHas('productos', ['nombre' => 'Casco integral M', 'stock' => 4]);
        $this->assertDatabaseMissing('productos', ['nombre' => 'Mal producto']);

        $this->assertDatabaseHas('categorias', ['nombre' => 'Repuestos de Motor']);
        $this->assertDatabaseHas('categorias', ['nombre' => 'Cascos y Seguridad']);

        // El stock inicial quedó registrado como movimiento de entrada con el lote.
        $filtro = Producto::where('nombre', 'Filtro de aire GN125')->firstOrFail();
        $this->assertSame('entrada', $filtro->movimientos()->first()->tipo);
        $this->assertSame(10, $filtro->movimientos()->first()->cantidad);
        $this->assertStringContainsString($resumen['lote'], $filtro->movimientos()->first()->motivo);

        // El reporte por lote agrupa todos los movimientos de esa importación.
        $movimientos = app(ImportacionProductosService::class)->movimientosDeLote($resumen['lote']);
        $this->assertCount(2, $movimientos);
        $this->assertTrue($movimientos->contains(fn ($m) => $m->producto->nombre === 'Casco integral M'));
    }

    public function test_importar_actualiza_productos_existentes_por_nombre(): void
    {
        $admin = User::factory()->create()->assignRole('Admin');
        $categoria = Categoria::create(['nombre' => 'Repuestos de Motor', 'tipo' => 'Repuesto']);
        Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Filtro de aire GN125',
            'precio_unitario' => 30.00,
            'stock' => 5,
            'umbral_alerta' => 2,
        ]);

        $filas = [
            ['numero' => 2, 'datos' => ['nombre' => 'Filtro de aire GN125', 'categoria' => 'Repuestos de Motor', 'precio_unitario' => '45.00', 'stock' => '10', 'umbral_alerta' => '5', 'costo' => '25.00', 'descripcion' => null, 'tipo' => 'Repuesto'], 'errores' => []],
        ];

        $resumen = app(ImportacionProductosService::class)->importar($filas, $admin);

        $this->assertSame(0, $resumen['creados']);
        $this->assertSame(1, $resumen['actualizados']);
        $this->assertSame(1, Producto::count());

        $producto = Producto::first();
        $this->assertSame('45.00', $producto->precio_unitario);
        $this->assertSame(10, $producto->stock);
        $this->assertSame('ajuste', $producto->movimientos()->first()->tipo);
        $this->assertStringContainsString($resumen['lote'], $producto->movimientos()->first()->motivo);
    }

    public function test_controlador_flujo_completo_de_importacion(): void
    {
        $admin = User::factory()->create()->assignRole('Admin');

        $csv = implode("\n", [
            'nombre;categoria;precio_unitario;stock;umbral_alerta;costo;descripcion;tipo',
            'Bombillo LED H4;Iluminación;70.00;20;5;;;Repuesto',
            'Espejos retrovisores;Accesorios;35.00;12;3;;;Accesorio',
        ]);

        // 1. Subir y analizar → vista previa con las filas en sesión.
        $this->actingAs($admin)
            ->post('/productos/importar/preview', ['archivo' => UploadedFile::fake()->createWithContent('productos.csv', $csv)])
            ->assertRedirect('/productos/importar');

        $this->actingAs($admin)->get('/productos/importar')
            ->assertOk()
            ->assertSee('2 fila(s) analizadas')
            ->assertSee('Bombillo LED H4')
            ->assertSee('Nuevo')
            ->assertSee('Confirmar importación');

        // 2. Confirmar la importación → resumen con lote y reporte descargable.
        $this->actingAs($admin)
            ->post('/productos/importar')
            ->assertRedirect('/productos/importar');

        $this->actingAs($admin)->get('/productos/importar')
            ->assertOk()
            ->assertSee('Importación ejecutada')
            ->assertSee('Lote IMP-')
            ->assertSee('Descargar reporte de movimientos');

        $this->assertDatabaseHas('productos', ['nombre' => 'Bombillo LED H4', 'stock' => 20]);
        $this->assertDatabaseHas('productos', ['nombre' => 'Espejos retrovisores', 'stock' => 12]);
        $this->assertDatabaseHas('categorias', ['nombre' => 'Iluminación']);

        // 3. El reporte CSV de movimientos del lote se descarga.
        $lote = Producto::where('nombre', 'Bombillo LED H4')->firstOrFail()->movimientos()->firstOrFail()->motivo;
        preg_match('/IMP-\d{8}-\d{6}/', $lote, $coincidencias);

        $this->actingAs($admin)->get('/productos/importar/movimientos/'.$coincidencias[0])
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload('movimientos-'.$coincidencias[0].'.csv');
    }

    public function test_controlador_requiere_permiso_y_valida_el_archivo(): void
    {
        $vendedor = User::factory()->create()->assignRole('Vendedor');
        $this->actingAs($vendedor)->get('/productos/importar')->assertForbidden();
        $this->actingAs($vendedor)->post('/productos/importar/preview', ['archivo' => UploadedFile::fake()->create('x.csv')])->assertForbidden();

        $admin = User::factory()->create()->assignRole('Admin');
        $this->actingAs($admin)->post('/productos/importar/preview', [])->assertSessionHasErrors('archivo');
    }

    public function test_plantilla_csv_se_descarga(): void
    {
        $admin = User::factory()->create()->assignRole('Admin');

        $this->actingAs($admin)->get('/productos/importar/plantilla')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_reporte_de_lote_rechaza_formato_invalido_y_requiere_permiso(): void
    {
        $admin = User::factory()->create()->assignRole('Admin');
        $vendedor = User::factory()->create()->assignRole('Vendedor');

        $this->actingAs($admin)->get('/productos/importar/movimientos/INVALIDO')->assertNotFound();
        $this->actingAs($vendedor)->get('/productos/importar/movimientos/IMP-20260817-120000')->assertForbidden();
    }

    public function test_historial_guarda_lote_resumen_y_usuario(): void
    {
        $admin = User::factory()->create(['name' => 'Jefa de Inventario'])->assignRole('Admin');

        $filas = [
            ['numero' => 2, 'datos' => ['nombre' => 'Bujía NGK', 'categoria' => 'Encendido', 'precio_unitario' => '25.00', 'stock' => '15', 'umbral_alerta' => '3', 'costo' => '12.00', 'descripcion' => null, 'tipo' => 'Repuesto'], 'errores' => []],
            ['numero' => 3, 'datos' => ['nombre' => 'Fila rota', 'categoria' => 'X', 'precio_unitario' => '', 'stock' => '1', 'umbral_alerta' => '0', 'costo' => null, 'descripcion' => null, 'tipo' => null], 'errores' => ['precio inválido (mayor a 0)']],
        ];

        $resumen = app(ImportacionProductosService::class)->importar($filas, $admin);

        $this->assertDatabaseHas('importaciones', [
            'lote' => $resumen['lote'],
            'creados' => 1,
            'actualizados' => 0,
            'errores' => 1,
            'total_filas' => 2,
            'user_id' => $admin->id,
        ]);

        // La página de historial muestra el lote, el resumen y el botón de reporte.
        $this->actingAs($admin)->get('/productos/importar/historial')
            ->assertOk()
            ->assertSee($resumen['lote'])
            ->assertSee('Jefa de Inventario')
            ->assertSee('Descargar reporte');
    }

    public function test_historial_requiere_permiso_y_exige_autenticacion(): void
    {
        $this->get('/productos/importar/historial')->assertRedirect('/login');

        $vendedor = User::factory()->create()->assignRole('Vendedor');
        $this->actingAs($vendedor)->get('/productos/importar/historial')->assertForbidden();
    }

    public function test_vista_previa_distingue_actualizar_de_crear(): void
    {
        $admin = User::factory()->create()->assignRole('Admin');
        $categoria = Categoria::create(['nombre' => 'Repuestos de Motor', 'tipo' => 'Repuesto']);
        Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Bombillo LED H4',
            'precio_unitario' => 60.00,
            'stock' => 10,
            'umbral_alerta' => 3,
        ]);

        $csv = implode("\n", [
            'nombre;categoria;precio_unitario;stock',
            'Bombillo LED H4;Repuestos de Motor;70.00;20',
            'Freno delantero;Frenos;55.00;8',
        ]);

        $this->actingAs($admin)
            ->post('/productos/importar/preview', ['archivo' => UploadedFile::fake()->createWithContent('productos.csv', $csv)])
            ->assertRedirect('/productos/importar');

        $this->actingAs($admin)->get('/productos/importar')
            ->assertOk()
            ->assertSee('Actualizar')
            ->assertSee('Nuevo')
            ->assertSee('sin crear duplicados');
    }
}
