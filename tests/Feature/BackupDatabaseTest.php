<?php

namespace Tests\Feature;

use App\Console\Commands\BackupDatabase;
use App\Mail\RespaldoMail;
use App\Models\Respaldo;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupDatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_modo_pretend_muestra_el_comando_sin_ejecutar(): void
    {
        $this->artisan('backup:database', ['--pretend' => true])
            ->expectsOutputToContain('[pretend]')
            ->assertExitCode(0);
    }

    public function test_mensaje_del_comando_muestra_la_ruta_real_del_disco(): void
    {
        Storage::fake('local');

        // El mensaje debe anunciar la ruta real del disco `local` (que en
        // Laravel 11+ vive en storage/app/private), no una ruta hardcodeada.
        // Con el disco fake la ruta real apunta a un directorio temporal,
        // así que el mensaje no puede contener `storage/app/backups`.
        $this->artisan('backup:database', ['--pretend' => true])
            ->expectsOutputToContain('Respaldo → '.Storage::disk('local')->path('backups'))
            ->assertExitCode(0);
    }

    public function test_purga_respaldos_fuera_de_la_ventana_de_retencion(): void
    {
        Storage::fake('local');

        $viejo = 'backups/db-apz-2020-01-01-000000.sql.gz';
        $nuevo = 'backups/db-apz-'.now()->format('Y-m-d').'-120000.sql.gz';

        Storage::disk('local')->put($viejo, 'contenido viejo');
        Storage::disk('local')->put($nuevo, 'contenido nuevo');

        $comando = new BackupDatabase;
        $eliminados = $comando->limpiarRespalvosViejos('backups', 7);

        $this->assertSame(1, $eliminados);
        Storage::disk('local')->assertMissing($viejo);
        Storage::disk('local')->assertExists($nuevo);
    }

    public function test_retencion_de_7_dias_con_archivos_en_el_limite(): void
    {
        Storage::fake('local');

        // Un respaldo con fecha exactamente en el límite de la ventana (hoy - 7 días)
        // debe conservarse; uno de 8 días atrás debe purgarse.
        $enElLimite = 'backups/db-apz-'.now()->subDays(7)->format('Y-m-d').'-000000.sql.gz';
        $fuera = 'backups/db-apz-'.now()->subDays(8)->format('Y-m-d').'-000000.sql.gz';

        Storage::disk('local')->put($enElLimite, 'contenido en el límite');
        Storage::disk('local')->put($fuera, 'contenido fuera de la ventana');

        $comando = new BackupDatabase;
        $eliminados = $comando->limpiarRespalvosViejos('backups', 7);

        $this->assertSame(1, $eliminados);
        Storage::disk('local')->assertExists($enElLimite);
        Storage::disk('local')->assertMissing($fuera);
    }

    public function test_no_toca_archivos_que_no_son_respaldos(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put('backups/nota.txt', 'no es un respaldo');

        $comando = new BackupDatabase;
        $eliminados = $comando->limpiarRespalvosViejos('backups', 7);

        $this->assertSame(0, $eliminados);
        Storage::disk('local')->assertExists('backups/nota.txt');
    }

    public function test_respaldo_exitoso_registra_historial_y_notifica_al_admin(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['email' => 'admin@prueba.com'])->assignRole('Admin');

        $comando = new BackupDatabase;
        $comando->registrarYNotificar(true, 'backups/db-apz-2026-08-17-100000.sql.gz', 41984, null);

        $this->assertDatabaseHas('respaldos', [
            'estado' => 'exitoso',
            'archivo' => 'backups/db-apz-2026-08-17-100000.sql.gz',
            'tamano_bytes' => 41984,
        ]);

        // El destinatario por defecto es el primer usuario Admin del sistema
        // (el sembrado por RoleSeeder), salvo que exista BACKUP_NOTIFY_EMAIL.
        Mail::assertSent(RespaldoMail::class, fn (RespaldoMail $mail) => $mail->hasTo('admin@apzmotors.com'));
    }

    public function test_respaldo_fallido_registra_historial_con_mensaje_y_notifica(): void
    {
        Mail::fake();

        $comando = new BackupDatabase;
        $comando->registrarYNotificar(false, null, null, 'mysqldump: no se encontró el comando.');

        $this->assertDatabaseHas('respaldos', [
            'estado' => 'fallido',
            'mensaje' => 'mysqldump: no se encontró el comando.',
        ]);

        Mail::assertSent(RespaldoMail::class, fn (RespaldoMail $mail) => $mail->hasTo('admin@apzmotors.com'));
    }

    public function test_backup_notify_email_gana_sobre_el_admin_sembrado(): void
    {
        Mail::fake();
        config(['database.backup.notify_email' => 'sysadmin@apzmotors.com']);

        $comando = new BackupDatabase;
        $comando->registrarYNotificar(true, 'backups/db.sql.gz', 100, null);

        Mail::assertSent(RespaldoMail::class, fn (RespaldoMail $mail) => $mail->hasTo('sysadmin@apzmotors.com'));
    }

    public function test_solo_admin_accede_al_historial_de_respaldos(): void
    {
        $vendedor = User::factory()->create()->assignRole('Vendedor');
        $this->actingAs($vendedor)->get('/respaldos')->assertForbidden();

        $admin = User::factory()->create(['name' => 'Admin de prueba'])->assignRole('Admin');
        $this->actingAs($admin)->get('/respaldos')
            ->assertOk()
            ->assertSee('Respaldos de base de datos');
    }

    public function test_descarga_el_archivo_de_respaldo_si_existe(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create()->assignRole('Admin');

        $archivo = 'backups/db-apz-'.now()->format('Y-m-d-Hi00').'.sql.gz';
        Storage::disk('local')->put($archivo, gzencode('contenido del respaldo', 6));
        $respaldo = Respaldo::create([
            'archivo' => $archivo,
            'tamano_bytes' => 1000,
            'estado' => 'exitoso',
            'ejecutado_en' => now(),
        ]);

        $this->actingAs($admin)
            ->get("/respaldos/{$respaldo->id}/descargar")
            ->assertOk()
            ->assertDownload(basename($archivo));
    }

    public function test_descarga_redirige_con_error_si_el_archivo_ya_no_existe(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create()->assignRole('Admin');

        // Registro apuntando a un archivo purgado por la retención.
        $respaldo = Respaldo::create([
            'archivo' => 'backups/db-apz-2020-01-01-000000.sql.gz',
            'tamano_bytes' => 1000,
            'estado' => 'exitoso',
            'ejecutado_en' => now(),
        ]);

        $this->actingAs($admin)
            ->from('/respaldos')
            ->get("/respaldos/{$respaldo->id}/descargar")
            ->assertRedirect('/respaldos')
            ->assertSessionHas('status-error');
    }

    public function test_solo_admin_puede_descargar_un_respaldo(): void
    {
        Storage::fake('local');
        $vendedor = User::factory()->create()->assignRole('Vendedor');

        $respaldo = Respaldo::create([
            'archivo' => 'backups/db-apz-2026-08-17-100000.sql.gz',
            'estado' => 'exitoso',
            'ejecutado_en' => now(),
        ]);

        $this->actingAs($vendedor)
            ->get("/respaldos/{$respaldo->id}/descargar")
            ->assertForbidden();
    }
}
