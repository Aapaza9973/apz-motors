$workDir = (Resolve-Path 'D:\ALVARO\Proyectos\APZ*Motor*').Path
$logDir = Join-Path $workDir '.freebuff'
$log = Join-Path $logDir 'preview-95fd890e-d3fd-411b-a3b7-3d1bac3bff11.log'
$logErr = "$log.err"
$php = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe'

$p = Start-Process $php -ArgumentList 'artisan','serve','--host=127.0.0.1','--port=8000' -WorkingDirectory $workDir -RedirectStandardOutput $log -RedirectStandardError $logErr -WindowStyle Hidden -PassThru
Write-Output "PHP_PID=$($p.Id)"
