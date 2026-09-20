$base = Resolve-Path 'D:\ALVARO\Proyectos\APZ*Motor*'
$bat = Join-Path $base 'apz-motors\.freebuff\start-php.bat'
$p = Start-Process cmd.exe -ArgumentList "/c","`"$bat`"" -WindowStyle Hidden -PassThru
"$($p.Id)" | Set-Content (Join-Path $base '.freebuff\php.pid')
