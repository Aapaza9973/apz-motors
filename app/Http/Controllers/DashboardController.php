<?php

namespace App\Http\Controllers;

use App\Models\AlertaStock;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $hoy = now()->startOfDay();

        $productosTotales = Producto::count();
        $bajoStock = Producto::whereColumn('stock', '<=', 'umbral_alerta')->count();

        return view('dashboard', [
            'ventasHoy' => Venta::where('fecha', '>=', $hoy)->count(),
            'ingresosHoy' => Venta::where('fecha', '>=', $hoy)->where('estado', '!=', 'Cancelada')->sum('total'),
            'productosTotales' => $productosTotales,
            'categoriasTotales' => Categoria::count(),
            'productosBajoStock' => $bajoStock,
            // Salud del inventario para el tacómetro: % de productos sobre su umbral.
            'stockSalud' => $productosTotales > 0 ? (int) round(($productosTotales - $bajoStock) / $productosTotales * 100) : 0,
            'clientesTotales' => Cliente::count(),
            'ventasPendientes' => Venta::where('estado', 'Pendiente')->count(),
            'ultimasVentas' => Venta::with('cliente', 'usuario')->latest('fecha')->take(5)->get(),
            'alertasRecientes' => AlertaStock::with('producto')->where('leida', false)->latest()->take(5)->get(),
            'productosBajoStockLista' => Producto::whereColumn('stock', '<=', 'umbral_alerta')->with('categoria')->take(8)->get(),
        ]);
    }
}
