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
| **Cierre de caja** | Arqueo por vendedor/día: resumen, totales por método de pago y registro del cierre (una vez por día) |
| **Reportes** | Ventas por rango (hoy/semana/mes/personalizado), productos más vendidos, inventario con valor en stock; exportación **PDF** (DomPDF) y **Excel/CSV** con identidad corporativa |
| **Alertas de stock** | Automáticas al bajar del umbral, bandeja con "marcar leída" y notificación en la barra |
| **Catálogo público** | Página pública (`/catalogo`) con banner de marca, filtros por categoría y disponibilidad en tiempo real |
| **Tablero de instrumentos** | KPIs del día + panel de instrumentos: tacómetro de ventas y medidor de salud de stock (SVG con datos reales) |
| **Respaldo diario** | `php artisan backup:database` (mysqldump + gzip, retención 7 días) programado en el scheduler |
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

## 🧪 Tests

```bash
php artisan test   # 75 tests / 224 aserciones (SQLite en memoria, aislado)
```

Cobertura: ventas y stock (decremento, alertas, bloqueo por stock insuficiente), accesos por rol (200/403), pagos simulados, devoluciones, exportaciones PDF/CSV, cierre de caja, catálogo público y comando de respaldo.

## 🔄 CI (GitHub Actions)

`.github/workflows/tests.yml` — en cada push/PR: PHP 8.3 + Node 20, build de assets, suite PHPUnit sobre SQLite en memoria y smoke test de migraciones + seeders sobre MySQL 8 (servicio contenedor).

## 🧭 Arquitectura

```
app/
├── Console/Commands/BackupDatabase.php   # respaldo diario con retención
├── Exports/CsvExporter.php               # CSV con BOM UTF-8 y separador ;
├── Http/Controllers/                     # Admin/ · Vendedor/ · módulos
├── Models/                               # 10 modelos del dominio
├── Providers/                            # Gates + Telescope (auditoría)
└── Services/                             # Venta, Inventory, Payment, Devolucion, Caja
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
- [ ] Pedidos en línea desde el catálogo · Fidelización de clientes · Notificaciones por correo · Deploy en VPS (Nginx + PHP-FPM + SSL + backups)

## 📄 Licencia

Proyecto interno de **APZ Motor's** — La Paz, Bolivia. "Tu ruta, nuestro compromiso."
