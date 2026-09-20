<?php

namespace Tests\Feature;

use App\Models\EncuestaCatalogo;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncuestaCatalogoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_visitante_puede_responder_la_encuesta_sin_cuenta(): void
    {
        $respuesta = $this->post(route('encuesta.store'), [
            'satisfaccion' => 5,
            'facilidad_encontrar' => 'facil',
            'falta' => 'Pastillas para Titan 150',
            'comentario' => 'Me gustó el catálogo',
            'origen' => 'catalogo',
        ]);

        $respuesta->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('encuestas_catalogo', [
            'satisfaccion' => 5,
            'facilidad_encontrar' => 'facil',
            'falta' => 'Pastillas para Titan 150',
            'origen' => 'catalogo',
        ]);
    }

    public function test_encuesta_guarda_campos_opcionales_nulos_si_van_vacios(): void
    {
        $this->post(route('encuesta.store'), [
            'satisfaccion' => 2,
            'facilidad_encontrar' => 'dificil',
        ])->assertRedirect()->assertSessionHas('status');

        $encuesta = EncuestaCatalogo::sole();

        $this->assertSame(2, $encuesta->satisfaccion);
        $this->assertNull($encuesta->falta);
        $this->assertNull($encuesta->comentario);
    }

    public function test_encuesta_valida_los_campos(): void
    {
        $this->from('/catalogo')->post(route('encuesta.store'), [
            'satisfaccion' => 9,
            'facilidad_encontrar' => 'mas-o-menos',
        ])->assertSessionHasErrors(['satisfaccion', 'facilidad_encontrar']);

        $this->assertDatabaseCount('encuestas_catalogo', 0);
    }

    public function test_widget_aparece_en_el_catalogo_y_en_la_ficha(): void
    {
        $this->get('/catalogo')
            ->assertOk()
            ->assertSee('Encuesta exprés del catálogo')
            ->assertSee(route('encuesta.store'), false);

        $this->get('/catalogo/productos/99999')->assertNotFound();
    }

    public function test_admin_ve_el_resumen_y_las_respuestas(): void
    {
        EncuestaCatalogo::create(['satisfaccion' => 4, 'facilidad_encontrar' => 'facil', 'falta' => 'Cubiertos traseros']);
        EncuestaCatalogo::create(['satisfaccion' => 1, 'facilidad_encontrar' => 'dificil', 'comentario' => 'Muy lento el buscador']);

        $admin = User::role('Admin')->firstOrFail();

        $this->actingAs($admin)->get(route('encuestas.index'))
            ->assertOk()
            ->assertSee('Encuesta de mejora del catálogo')
            ->assertSee('2.50 / 5')
            ->assertSee('Cubiertos traseros')
            ->assertSee('Muy lento el buscador');
    }

    public function test_vendedor_sin_permiso_no_ve_las_respuestas(): void
    {
        $vendedor = User::role('Vendedor')->firstOrFail();

        $this->actingAs($vendedor)->get(route('encuestas.index'))->assertForbidden();
    }

    public function test_visitante_no_ve_la_vista_admin(): void
    {
        $this->get(route('encuestas.index'))->assertRedirect('/login');
    }
}
