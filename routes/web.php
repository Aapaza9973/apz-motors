<?php

use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\ProductoController;
use App\Http\Controllers\Admin\ProductoImportacionController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\Admin\RespaldoController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AlertaController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DevolucionController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Vendedor\CajaController;
use App\Http\Controllers\Vendedor\ClienteController;
use App\Http\Controllers\Vendedor\VentaController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Alertas de stock (visibles para todo el personal interno).
    Route::get('/alertas', [AlertaController::class, 'index'])->name('alertas.index');
    Route::post('/alertas/{alerta}/leida', [AlertaController::class, 'marcarLeida'])->name('alertas.marcar-leida');
    Route::post('/alertas/leidas', [AlertaController::class, 'marcarTodasLeidas'])->name('alertas.marcar-todas-leidas');

    // ---- Productos (matriz: Admin C/R/U/D · Vendedor R · Inventario C/R/U/D) ----
    Route::middleware('can:ver productos')->get('/productos', [ProductoController::class, 'index'])->name('productos.index');
    Route::middleware('can:crear productos')->group(function () {
        Route::get('/productos/crear', [ProductoController::class, 'create'])->name('productos.create');
        Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
        // Importación masiva desde CSV (validación previa + reporte).
        Route::get('/productos/importar', [ProductoImportacionController::class, 'formulario'])->name('productos.importar');
        Route::post('/productos/importar/preview', [ProductoImportacionController::class, 'vistaPrevia'])->name('productos.importar.preview');
        Route::post('/productos/importar', [ProductoImportacionController::class, 'importar'])->name('productos.importar.ejecutar');
        Route::post('/productos/importar/cancelar', [ProductoImportacionController::class, 'cancelar'])->name('productos.importar.cancelar');
        Route::get('/productos/importar/plantilla', [ProductoImportacionController::class, 'plantilla'])->name('productos.importar.plantilla');
        // Historial de importaciones: lotes con resumen y reporte descargable.
        Route::get('/productos/importar/historial', [ProductoImportacionController::class, 'historial'])->name('productos.importar.historial');
        // Reporte de movimientos de stock de una importación (lote IMP-…).
        Route::get('/productos/importar/movimientos/{lote}', [ProductoImportacionController::class, 'reporteMovimientos'])->name('productos.importar.reporte');
    });
    Route::middleware('can:ver productos')->get('/productos/{producto}', [ProductoController::class, 'show'])->name('productos.show');
    Route::middleware('can:editar productos')->group(function () {
        Route::get('/productos/{producto}/editar', [ProductoController::class, 'edit'])->name('productos.edit');
        Route::put('/productos/{producto}', [ProductoController::class, 'update'])->name('productos.update');
    });
    Route::middleware('can:eliminar productos')->delete('/productos/{producto}', [ProductoController::class, 'destroy'])->name('productos.destroy');

    // ---- Categorías (matriz: Admin C/R/U/D · Inventario C/R/U/D) ----
    Route::middleware('can:ver categorias')->get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');
    Route::middleware('can:crear categorias')->group(function () {
        Route::get('/categorias/crear', [CategoriaController::class, 'create'])->name('categorias.create');
        Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');
    });
    Route::middleware('can:editar categorias')->group(function () {
        Route::get('/categorias/{categoria}/editar', [CategoriaController::class, 'edit'])->name('categorias.edit');
        Route::put('/categorias/{categoria}', [CategoriaController::class, 'update'])->name('categorias.update');
    });
    Route::middleware('can:eliminar categorias')->delete('/categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');

    // ---- Clientes (matriz: Admin C/R/U/D · Vendedor C/R · Inventario R) ----
    Route::middleware('can:ver clientes')->get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::middleware('can:crear clientes')->group(function () {
        Route::get('/clientes/crear', [ClienteController::class, 'create'])->name('clientes.create');
        Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
    });
    Route::middleware('can:ver clientes')->get('/clientes/{cliente}', [ClienteController::class, 'show'])->name('clientes.show');
    Route::middleware('can:editar clientes')->group(function () {
        Route::get('/clientes/{cliente}/editar', [ClienteController::class, 'edit'])->name('clientes.edit');
        Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');
    });
    Route::middleware('can:eliminar clientes')->delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');

    // Fidelización: ajuste manual de puntos del cliente (permiso de editar clientes).
    Route::middleware('can:editar clientes')->post('/clientes/{cliente}/puntos/ajustar', [ClienteController::class, 'ajustarPuntos'])->name('clientes.puntos.ajustar');

    // ---- Ventas / Punto de venta (matriz: Admin C/R/U/D · Vendedor C/R) ----
    Route::middleware('can:ver ventas')->get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
    Route::middleware('can:crear ventas')->group(function () {
        Route::get('/ventas/crear', [VentaController::class, 'create'])->name('ventas.create');
        Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');
    });
    Route::middleware('can:ver ventas')->get('/ventas/{venta}', [VentaController::class, 'show'])->name('ventas.show');
    // Auditoría de impresión del comprobante (quién, venta, origen pos/manual en Telescope).
    Route::middleware('can:ver ventas')->post('/ventas/{venta}/imprimir', [VentaController::class, 'imprimir'])->name('ventas.imprimir');

    // Preferencias de impresión del usuario autenticado (papel y autoimpresión POS).
    Route::post('/preferencias/comprobante', [VentaController::class, 'guardarPreferencias'])->name('preferencias.comprobante');

    // ---- Pagos en línea (Fase 2): Stripe / PayPal / simulación ----
    Route::middleware('can:ver ventas')->group(function () {
        Route::post('/ventas/{venta}/pagar', [PagoController::class, 'iniciar'])->name('pagos.iniciar');
        Route::get('/pagos/{venta}/retorno', [PagoController::class, 'retorno'])->name('pagos.retorno');
        Route::get('/pagos/{venta}/simular/{metodo}', [PagoController::class, 'simular'])->name('pagos.simular');
    });

    // ---- Devoluciones (matriz ampliada: Admin ver/crear/aprobar · Vendedor ver/crear) ----
    Route::middleware('can:ver devoluciones')->get('/devoluciones', [DevolucionController::class, 'index'])->name('devoluciones.index');
    Route::middleware('can:crear devoluciones')->post('/ventas/{venta}/devoluciones', [DevolucionController::class, 'store'])->name('devoluciones.store');
    Route::middleware('can:aprobar devoluciones')->post('/devoluciones/{devolucion}/aprobar', [DevolucionController::class, 'aprobar'])->name('devoluciones.aprobar');
    Route::middleware('can:aprobar devoluciones')->post('/devoluciones/{devolucion}/rechazar', [DevolucionController::class, 'rechazar'])->name('devoluciones.rechazar');

    // ---- Cierre de caja (Admin · Vendedor): reporte del día + registro del cierre ----
    Route::middleware('can:ver cierres de caja')->get('/caja', [CajaController::class, 'index'])->name('caja.index');
    Route::middleware('can:crear cierres de caja')->group(function () {
        Route::get('/caja/cierre', [CajaController::class, 'create'])->name('caja.create');
        Route::post('/caja/cierre', [CajaController::class, 'store'])->name('caja.store');
    });
    Route::middleware('can:ver cierres de caja')->get('/caja/{cierre}/pdf', [CajaController::class, 'exportarPdf'])->name('caja.pdf');

    // ---- Reportes (matriz: Admin R · Vendedor R limitado · Inventario R inventario) ----
    Route::middleware('can:ver reportes')->group(function () {
        Route::get('/reportes/ventas', [ReporteController::class, 'ventas'])->name('reportes.ventas');
        Route::get('/reportes/ventas/exportar/pdf', [ReporteController::class, 'exportarPdfVentas'])->name('reportes.ventas.pdf');
        Route::get('/reportes/ventas/exportar/csv', [ReporteController::class, 'exportarCsvVentas'])->name('reportes.ventas.csv');
        Route::get('/reportes/inventario', [ReporteController::class, 'inventario'])->name('reportes.inventario');
        Route::get('/reportes/inventario/exportar/pdf', [ReporteController::class, 'exportarPdfInventario'])->name('reportes.inventario.pdf');
        Route::get('/reportes/inventario/exportar/csv', [ReporteController::class, 'exportarCsvInventario'])->name('reportes.inventario.csv');
    });

    // ---- Pedidos en línea (bandeja interna: Admin ver/confirmar · Vendedor ver/confirmar) ----
    Route::middleware('can:ver pedidos')->group(function () {
        Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
        Route::get('/pedidos/{pedido}', [PedidoController::class, 'show'])->name('pedidos.show');
    });
    Route::middleware('can:confirmar pedidos')->group(function () {
        Route::post('/pedidos/{pedido}/confirmar', [PedidoController::class, 'confirmar'])->name('pedidos.confirmar');
        Route::post('/pedidos/{pedido}/cancelar', [PedidoController::class, 'cancelar'])->name('pedidos.cancelar');
    });

    // ---- Respaldos de base de datos (historial consultable, solo Admin) ----
    Route::middleware('can:ver respaldos')->get('/respaldos', [RespaldoController::class, 'index'])->name('respaldos.index');
    Route::middleware('can:ver respaldos')->get('/respaldos/{respaldo}/descargar', [RespaldoController::class, 'descargar'])->name('respaldos.descargar');

    // ---- Usuarios y roles (solo Admin) ----
    Route::middleware('can:ver usuarios')->get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::middleware('can:crear usuarios')->group(function () {
        Route::get('/usuarios/crear', [UserController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
    });
    Route::middleware('can:editar usuarios')->group(function () {
        Route::get('/usuarios/{usuario}/editar', [UserController::class, 'edit'])->name('usuarios.edit');
        Route::put('/usuarios/{usuario}', [UserController::class, 'update'])->name('usuarios.update');
    });
    Route::middleware('can:eliminar usuarios')->delete('/usuarios/{usuario}', [UserController::class, 'destroy'])->name('usuarios.destroy');

    // Perfil (Breeze).
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Catálogo público de repuestos y pedidos en línea (sin autenticación).
Route::get('/catalogo', [CatalogoController::class, 'index'])->name('catalogo.index');
Route::get('/catalogo/productos/{producto}', [CatalogoController::class, 'show'])->name('catalogo.producto');
Route::post('/catalogo/carrito/agregar', [CarritoController::class, 'agregar'])->name('carrito.agregar');
Route::get('/catalogo/carrito', [CarritoController::class, 'ver'])->name('carrito.ver');
Route::post('/catalogo/carrito/actualizar', [CarritoController::class, 'actualizar'])->name('carrito.actualizar');
Route::post('/catalogo/carrito/eliminar', [CarritoController::class, 'eliminar'])->name('carrito.eliminar');
Route::get('/catalogo/checkout', [PedidoController::class, 'checkout'])->name('pedidos.checkout');
Route::post('/catalogo/pedidos', [PedidoController::class, 'store'])->name('pedidos.store');
Route::get('/catalogo/pedidos/{pedido}', [PedidoController::class, 'confirmacion'])->name('pedidos.confirmacion');
Route::get('/catalogo/consultar', [PedidoController::class, 'consultarForm'])->name('pedidos.consultar');
Route::post('/catalogo/consultar', [PedidoController::class, 'consultar'])->name('pedidos.consultar-enviar');
Route::get('/catalogo/pedidos/{pedido}/estado', [PedidoController::class, 'estado'])->name('pedidos.estado');
// Pago en línea del catálogo (retorno de pasarela y simulación sin claves API).
Route::get('/catalogo/pedidos/{pedido}/pago/{metodo}', [PedidoController::class, 'pagoRetorno'])->name('pedidos.pago.retorno');
Route::get('/catalogo/pedidos/{pedido}/pago/simular/{metodo}', [PedidoController::class, 'pagoSimular'])->name('pedidos.pago.simular');
// Acciones del cliente desde el correo (enlace seguro con token).
Route::get('/catalogo/pedidos/{pedido}/confirmar/{token}', [PedidoController::class, 'confirmarCliente'])->name('pedidos.confirmar-cliente');
Route::get('/catalogo/pedidos/{pedido}/cancelar/{token}', [PedidoController::class, 'cancelarCliente'])->name('pedidos.cancelar-cliente');

// Webhook de Stripe (sin CSRF ni autenticación: lo firma la propia pasarela).
Route::post('/webhooks/stripe', [PagoController::class, 'webhookStripe'])
    ->name('webhooks.stripe')
    ->withoutMiddleware(ValidateCsrfToken::class);

require __DIR__.'/auth.php';
