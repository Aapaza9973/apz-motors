$base = Resolve-Path 'D:\ALVARO\Proyectos\APZ*Motor*'
$workDir = Join-Path $base 'apz-motors'
$log = Join-Path $base '.freebuff\preview-95fd890e-d3fd-411b-a3b7-3d1bac3bff11-vite.log'
$logErr = "$log.err"

Start-Process 'node.exe' -ArgumentList 'C:\SistAlvaro\node_modules\npm\bin\npm-cli.js','run','dev' -WorkingDirectory $workDir -RedirectStandardOutput $log -RedirectStandardError $logErr -WindowStyle Hidden -PassThru | ForEach-Object { $_.Id }
