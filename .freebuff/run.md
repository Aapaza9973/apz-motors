# APZ Motors — Preview Run Doc

## Reproduce uncommitted artifacts

1. Copy `.env` from the main checkout (already present — same worktree).
2. Dependencies: `node_modules` and `vendor` are already installed.

## Run the server

This is a **Laravel 13** project. It needs two processes:

### 1. PHP backend (`php artisan serve`)

```bash
cd apz-motors
php artisan serve --host=127.0.0.1 --port=8000 &
```

### 2. Vite frontend (CSS/JS hot-reload)

```bash
nohup /c/SistAlvaro/node.exe /c/SistAlvaro/node_modules/npm/bin/npm-cli.js run dev \
  --prefix "D:/ALVARO/Proyectos/APZ Motor's/apz-motors" > /tmp/vite.log 2>&1 &
disown
```

Vite runs on **port 5173** (falls back to 5174 if busy).

### Windows detach (PowerShell) — PHP server

The project path contains an apostrophe (`APZ Motor's`) which breaks inline
PowerShell. Use a `.ps1` script file instead:

```powershell
# launch-php.ps1 — run this from bash:
powershell -NoProfile -ExecutionPolicy Bypass -Command \
  "Start-Process powershell -ArgumentList '-NoProfile','-ExecutionPolicy','Bypass','-File','<worktree>\apz-motors\.freebuff\launch-php.ps1' -WindowStyle Hidden"
```

Or the bat-file approach (handles quoting naturally):

```
cmd.exe /c start /b cmd /c "cd /d "<path>" && php.exe artisan serve ..."
```

### Ports

| Service | Port |
|---------|------|
| Laravel (PHP) | 8000 |
| Vite | 5173 |

### Note on the apostrophe path

The directory `APZ Motor's` contains an apostrophe. When passing paths to
PowerShell inline, always double-quote the path (`"D:\...\APZ Motor's"`). For
`Start-Process` with `RedirectStandardOutput`, use `.ps1` script files or
`Resolve-Path` with a glob (`APZ*Motor*`) to resolve the path without literal
apostrophe escaping issues.
