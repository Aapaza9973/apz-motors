# APZ Motor's — Sistema de Ventas e Inventario

Sistema web para la tienda de repuestos y accesorios de motocicletas **APZ Motor's** (La Paz, Bolivia), construido con **Laravel 13**, **Blade + Tailwind CSS**, roles con **Spatie Laravel-Permission** y **MySQL/MariaDB**.

Incluye punto de venta (POS), control de inventario con trazabilidad, alertas de stock, pagos en línea (Stripe/PayPal), devoluciones, reportes exportables (PDF/CSV), cierre de caja por vendedor, respaldo diario de base de datos, monitoreo con Laravel Telescope y un catálogo público con la identidad de marca.

---

## ✨ Módulos

| Módulo | Descripción |
|---|---|
| **Autenticación** | Breeze en español, verificación de correo, traducciones propias (`lang/es/`), zona horaria `America/La_Paz` |
| **Roles y permisos** | 5 roles (Admin, Vendedor, Inventario, Soporte, Cliente) con la matriz del Documento Maestro; rutas protegidas por permiso |
| **Productos y categorías** | CRUD completo, stock, umbral de alerta y trazabilidad de movimientos (entrada, ajuste, venta, devolución) |
| **Punto de venta (POS)** | Carrito en vivo, descuento atómico de stock (`lockForUpdate`), comprobante imprimible |
| **Pagos en línea** | Stripe (Checkout Session + webhook firmado) y PayPal (REST); **modo simulación** automático sin claves API |
| **Devoluciones** | Solicitud con motivo, aprobación por Admin, reembolso y reposición automática de stock |
| **Cierre de caja** | Arqueo por vendedor/día: resumen, totales por método de pago, registro del cierre (una vez por día) y **exportación PDF** con identidad corporativa |
| **Reportes** | Ventas por rango (hoy/semana/mes/personalizado), productos más vendidos, inventario con valor en stock; exportación **PDF** (DomPDF) y **Excel/CSV** con identidad corporativa |
| **Alertas de stock** | Automáticas al bajar del umbral, bandeja con "marcar leída" y notificación en la barra |
| **Catálogo público** | Página pública (`/catalogo`) con banner de marca, filtros por categoría y disponibilidad en tiempo real |
| **Pedidos en línea** | Carrito en sesión → checkout con datos del cliente → **bandeja interna** donde el vendedor confirma y convierte en venta (revalida stock y genera cliente); **correos al cliente** por estado y **consulta pública de estado** por número + teléfono |
| **Tablero de instrumentos** | KPIs del día + panel de instrumentos: tacómetro de ventas y medidor de salud de stock (SVG con datos reales) |
| **Respaldo diario** | `php artisan backup:database` (mysqldump + gzip, retención 7 días) programado en el scheduler; **historial consultable** en la app y **correo** al administrador en cada ejecución |
| **Monitoreo** | Laravel Telescope (Fase 3) con gate de Admin y etiquetado de auditoría por usuario/rol |

## 🚀 Puesta en marcha

```bash
# 1. Dependencias
composer install
npm install && npm run build

# 2. Entorno (crear BD apz_motors en MySQL/MariaDB)
cp .env.example .env
php artisan key:generate

# 3. Migraciones y datos demo (3 usuarios, 6 categorías, 12 productos, 5 clientes, ventas…)
php artisan migrate:fresh --seed

# 4. Servidor
php artisan serve
```

**Usuarios demo** (contraseña `password` para todos):

| Correo | Rol |
|---|---|
| `admin@apzmotors.com` | Admin |
| `vendedor@apzmotors.com` | Vendedor |
| `inventario@apzmotors.com` | Inventario |

## 🔄 Respaldo diario

```bash
php artisan backup:database            # respaldo ahora (storage/app/private/backups)
php artisan backup:database --pretend  # muestra el comando sin ejecutar
php artisan schedule:list              # tarea diaria programada
```

En producción (VPS con cron):

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

Las rutas de `mysqldump`/`gzip` se configuran con `BACKUP_MYSQLDUMP_PATH` / `BACKUP_GZIP_PATH` en el `.env`.

### Notificación y historial

Cada ejecución del respaldo queda registrada en la tabla `respaldos` y se consulta desde **Administración → Respaldos** (solo Admin): resultado, archivo, tamaño y detalle del error si falló.

El comando además envía un correo al administrador con el resultado:

- **`BACKUP_NOTIFY_EMAIL`** en el `.env` define el destinatario; si queda vacío, se usa el correo del primer usuario con rol **Admin**.
- El correo incluye la identidad de marca, el archivo generado y su tamaño; un fallo del envío de correo **nunca** rompe el respaldo.

## 🛒 Pedidos en línea

El catálogo público permite armar pedidos sin cuenta: el visitante agrega repuestos al carrito (guardado en su sesión), completa sus datos en el checkout y el pedido cae en la **bandeja interna** (`Pedidos en línea` en el sidebar).

- El vendedor **confirma** el pedido: se revalida el stock actual, se crea o reutiliza el cliente y se genera la venta (descuento atómico de stock). También puede **cancelarlo**.
- El cliente recibe un **correo con la identidad de marca** en cada cambio de estado: *recibido* (al hacer el pedido), *confirmado* (con el número de venta) y *cancelado*. Solo se envía si dejó un correo, y un fallo de envío nunca rompe el flujo.
- **Consulta pública de estado** (`/catalogo/consultar`, enlazado en el pie del catálogo y en el correo): con el número de pedido y el teléfono, el cliente ve el estado actual sin estar autenticado (el teléfono valida que la página no se abra con datos ajenos).

## 🔍 Auditoría con Telescope

Laravel Telescope se instala en `TELESCOPE_ENABLED=true` (por defecto en local) y se abre en `/telescope` — el enlace del sidebar solo aparece para el rol **Admin**.

Cada request autenticado se etiqueta automáticamente con el usuario y el rol que lo ejecutó (`app/Providers/TelescopeServiceProvider.php`):

```
user:{id}   →  p. ej. user:1
rol:{rol}   →  p. ej. rol:Admin
```

Para auditar la actividad de una persona, abrí Telescope y filtrá por `user:1` (o por `rol:Vendedor` para ver el grupo): se ven sus logins, consultas, ventas registradas y movimientos de stock en orden cronológico, sin tener que revisar la base de datos.

## 🧪 Tests

```bash
php artisan test   # 94 tests / 306 aserciones (SQLite en memoria, aislado)
```

Cobertura: ventas y stock (decremento, alertas, bloqueo por stock insuficiente), accesos por rol (200/403), pagos simulados, devoluciones, exportaciones PDF/CSV, cierre de caja (incluida su exportación PDF), catálogo público, **pedidos en línea de punta a punta** (carrito → pedido → confirmación → venta, con stock insuficiente, cancelación, correos al cliente y consulta pública de estado), historial/notificación de respaldo.

## 🔄 CI (GitHub Actions)

`.github/workflows/tests.yml` — en cada push/PR: PHP 8.3 + Node 20, build de assets, suite PHPUnit sobre SQLite en memoria y smoke test de migraciones + seeders sobre MySQL 8 (servicio contenedor).

## 🧭 Arquitectura

```
app/
├── Console/Commands/BackupDatabase.php   # respaldo diario con retención + notificación
├── Exports/CsvExporter.php               # CSV con BOM UTF-8 y separador ;
├── Http/Controllers/                     # Admin/ · Vendedor/ · módulos
├── Mail/RespaldoMail.php                 # correo de éxito/fallo del respaldo
├── Models/                               # 13 modelos del dominio
├── Providers/                            # Gates + Telescope (auditoría)
├── Services/                             # Venta, Inventory, Payment, Devolucion, Caja, Pedido
└── Support/Carrito.php                   # carrito de sesión del catálogo público
```

- **Transacciones atómicas** en servicios: una venta valida stock, descuenta inventario y registra el pago en una sola transacción con `lockForUpdate` (el stock nunca queda negativo).
- **Spatie Laravel-Permission**: matriz del Documento Maestro + `Gate::before` para Admin.
- **Pagos**: `PaymentService` con drivers Stripe/PayPal y modo simulación automático si no hay claves API.

## 🎨 Sistema de diseño "Taller"

Decisión documentada: el Documento Maestro proponía Bootstrap; se adoptó **Tailwind CSS** (v3) con un sistema de diseño propio por la flexibilidad de tokens y componentes.

- **Paleta**: llama `#f54505` (marca) · carbon `#18191c` · plancha `#eceff1` (lienzo) · precaución `#ffb800` · ok/peligro `#16794b`/`#d92d20`.
- **Tipografía**: **Chakra Petch** (display), **Space Grotesk** (cuerpo), **IBM Plex Mono** (precios, stock, códigos, timestamps).
- **Firma**: cinta de precaución carbon/llama en carril, login, hero y tarjetas — la jerga del taller que codifica "esto requiere atención".
- **Identidad**: logo e icono reales de `Identidad APZ Motors/` (login, sidebar, favicon, comprobantes), banner de marca como fondo del login y del tablero (con scrim para contraste WCAG 4.5:1), y PDFs con logotipo, colores corporativos y números en mono.
- **Estados vacíos**: componente `x-empty-state` con la voz del taller ("Sin repuestos para este filtro", "Stock en calma"…).

## 🗺️ Roadmap

- [x] **Fase 1 — MVP**: inventario, POS, clientes, roles, alertas
- [x] **Fase 2 — Comercial**: pagos en línea (Stripe/PayPal), devoluciones, exportación de reportes
- [x] **Fase 3 — Operación**: cierre de caja, respaldo diario, Telescope, catálogo público, identidad de marca
- [x] **Pedidos en línea** desde el catálogo público (carrito → bandeja interna → venta)
- [ ] Fidelización de clientes · Notificaciones por correo de pedidos · Deploy en VPS (Nginx + PHP-FPM + SSL + backups)

## 📄 Licencia

Proyecto interno de **APZ Motor's** — La Paz, Bolivia. "Tu ruta, nuestro compromiso."
