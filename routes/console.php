<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Respaldo diario de la base de datos (Documento Maestro §6).
// En el servidor: `* * * * * cd /ruta/a/apz-motors && php artisan schedule:run >> /dev/null 2>&1`
Schedule::command('backup:database')
    ->dailyAt('02:00')
    ->timezone('America/La_Paz')
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('La tarea programada backup:database falló.');
    });
