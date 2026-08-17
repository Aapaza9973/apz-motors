<?php

namespace Tests\Feature;

use App\Console\Commands\BackupDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_modo_pretend_muestra_el_comando_sin_ejecutar(): void
    {
        $this->artisan('backup:database', ['--pretend' => true])
            ->expectsOutputToContain('[pretend]')
            ->assertExitCode(0);
    }

    public function test_purga_respaldos_fuera_de_la_ventana_de_retencion(): void
    {
        Storage::fake('local');

        $viejo = 'backups/db-apz-2020-01-01-000000.sql.gz';
        $nuevo = 'backups/db-apz-'.now()->format('Y-m-d').'-120000.sql.gz';

        Storage::disk('local')->put($viejo, 'contenido viejo');
        Storage::disk('local')->put($nuevo, 'contenido nuevo');

        $comando = new BackupDatabase();
        $eliminados = $comando->limpiarRespalvosViejos('backups', 7);

        $this->assertSame(1, $eliminados);
        Storage::disk('local')->assertMissing($viejo);
        Storage::disk('local')->assertExists($nuevo);
    }

    public function test_no_toca_archivos_que_no_son_respaldos(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put('backups/nota.txt', 'no es un respaldo');

        $comando = new BackupDatabase();
        $eliminados = $comando->limpiarRespalvosViejos('backups', 7);

        $this->assertSame(0, $eliminados);
        Storage::disk('local')->assertExists('backups/nota.txt');
    }
}
