<?php

namespace App\Console\Commands;

use App\Mail\RespaldoMail;
use App\Models\Respaldo;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database
        {--keep=7 : Número de días de historial a conservar}
        {--pretend : Muestra los comandos sin ejecutarlos}';

    protected $description = 'Respaldo diario de la base de datos con mysqldump y retención configurable (Documento Maestro §6).';

    public function handle(): int
    {
        $connection = Config::get('database.default');
        $host = Config::get("database.connections.{$connection}.host", '127.0.0.1');
        $port = Config::get("database.connections.{$connection}.port", 3306);
        $database = Config::get("database.connections.{$connection}.database");
        $username = Config::get("database.connections.{$connection}.username", 'root');
        $password = Config::get("database.connections.{$connection}.password");
        $mysqldump = config('database.backup.mysqldump_path', env('BACKUP_MYSQLDUMP_PATH', 'mysqldump'));

        if (blank($database)) {
            $this->registrarYNotificar(false, null, null, 'No se pudo determinar el nombre de la base de datos.');
            $this->error('No se pudo determinar el nombre de la base de datos.');

            return self::FAILURE;
        }

        $disk = Storage::disk('local');
        $carpeta = 'backups';
        $ruta = $carpeta.'/db-'.$database.'-'.now()->format('Y-m-d-His').'.sql.gz';

        $dump = array_filter([
            $mysqldump,
            "--host={$host}",
            "--port={$port}",
            "--user={$username}",
            $password !== null && $password !== '' ? "--password={$password}" : null,
            '--single-transaction',
            '--routines',
            '--triggers',
            $database,
        ]);

        $comando = implode(' ', array_map('escapeshellarg', $dump));

        $this->line("Respaldo → storage/app/{$ruta}");

        if ($this->option('pretend')) {
            $this->line('  [pretend] '.$comando.' | gzip');

            return self::SUCCESS;
        }

        $archivo = null;
        $tamanoBytes = null;
        $mensaje = null;

        try {
            $process = new Process($dump);
            $process->setTimeout(300)->mustRun();

            $comprimido = gzencode($process->getOutput(), 6);

            $disk->put($ruta, $comprimido);
            $archivo = $ruta;
            $tamanoBytes = strlen($comprimido);
        } catch (\Throwable $e) {
            $mensaje = $e->getMessage();
        }

        $this->registrarYNotificar($mensaje === null, $archivo, $tamanoBytes, $mensaje);

        if ($mensaje !== null) {
            Log::error('Backup de base de datos fallido.', ['error' => $mensaje]);
            $this->error('El respaldo falló: '.$mensaje);

            return self::FAILURE;
        }

        $eliminados = $this->limpiarRespalvosViejos($carpeta, (int) $this->option('keep'));

        Log::info('Backup de base de datos completado.', ['archivo' => $ruta, 'eliminados' => $eliminados]);
        $this->info("Respaldo completado — historial conservado: {$this->option('keep')} días ({$eliminados} archivo(s) purgado(s)).");

        return self::SUCCESS;
    }

    /**
     * Registra el resultado del respaldo en el historial consultable
     * (tabla `respaldos`) y notifica por correo al administrador.
     * Los fallos de correo nunca rompen el comando de respaldo.
     */
    public function registrarYNotificar(bool $exitoso, ?string $archivo, ?int $tamanoBytes, ?string $mensaje): void
    {
        Respaldo::create([
            'archivo' => $archivo,
            'tamano_bytes' => $tamanoBytes,
            'estado' => $exitoso ? 'exitoso' : 'fallido',
            'mensaje' => $mensaje,
            'ejecutado_en' => now(),
        ]);

        $destinatario = $this->destinatarioAdmin();

        if ($destinatario === null) {
            Log::warning('Backup: no hay administrador ni BACKUP_NOTIFY_EMAIL para notificar.', [
                'resultado' => $exitoso ? 'exitoso' : 'fallido',
            ]);

            return;
        }

        try {
            Mail::to($destinatario)->send(new RespaldoMail(
                estado: $exitoso ? 'exitoso' : 'fallido',
                archivo: $archivo,
                tamanoBytes: $tamanoBytes,
                mensaje: $mensaje,
            ));
        } catch (\Throwable $e) {
            Log::error('Backup: no se pudo enviar el correo de notificación.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Destinatario de la notificación: `BACKUP_NOTIFY_EMAIL` o, en su
     * defecto, el correo del primer usuario con rol Admin.
     */
    private function destinatarioAdmin(): ?string
    {
        if ($email = config('database.backup.notify_email')) {
            return $email;
        }

        return User::role('Admin')->first()?->email;
    }

    /**
     * Purga los respaldos anteriores a la ventana de retención
     * (la fecha se codifica en el nombre del archivo).
     *
     * @return int número de archivos eliminados
     */
    public function limpiarRespalvosViejos(string $carpeta, int $dias): int
    {
        $corte = now()->subDays($dias)->startOfDay();
        $eliminados = 0;

        foreach (Storage::disk('local')->files($carpeta) as $archivo) {
            if (! preg_match('/db-.*-(\d{4})-(\d{2})-(\d{2})-\d{6}\.sql\.gz/', basename($archivo), $m)) {
                continue;
            }

            $fecha = Carbon::create((int) $m[1], (int) $m[2], (int) $m[3])->startOfDay();

            if ($fecha->lt($corte)) {
                Storage::disk('local')->delete($archivo);
                $eliminados++;
            }
        }

        return $eliminados;
    }
}
