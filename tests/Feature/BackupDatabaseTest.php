<?php

namespace Tests\Feature;

use App\Console\Commands\BackupDatabase;
use App\Mail\RespaldoMail;
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

    public function test_respaldo_exitoso_registra_historial_y_notifica_al_admin(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['email' => 'admin@prueba.com'])->assignRole('Admin');

        $comando = new BackupDatabase();
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

        $comando = new BackupDatabase();
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

        $comando = new BackupDatabase();
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
}
