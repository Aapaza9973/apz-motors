<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\InventoryService;
use App\Services\VentaService;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = collect([
            ['nombre' => 'Repuestos de Motor', 'tipo' => 'Repuesto'],
            ['nombre' => 'Frenos', 'tipo' => 'Repuesto'],
            ['nombre' => 'Transmisión', 'tipo' => 'Repuesto'],
            ['nombre' => 'Iluminación', 'tipo' => 'Accesorio'],
            ['nombre' => 'Cascos y Seguridad', 'tipo' => 'Accesorio'],
            ['nombre' => 'Carenados', 'tipo' => 'Accesorio'],
        ])->map(fn ($c) => Categoria::firstOrCreate(['nombre' => $c['nombre']], $c));

        $productos = [
            ['nombre' => 'Kit de arrastre (piñón + corona + cadena)', 'tipo' => 'Transmisión', 'precio' => 185.00, 'costo' => 120.00, 'stock' => 12, 'umbral' => 5],
            ['nombre' => 'Batería YTX9-BS 12V', 'tipo' => 'Repuestos de Motor', 'precio' => 320.00, 'costo' => 210.00, 'stock' => 8, 'umbral' => 3],
            ['nombre' => 'Filtro de aire de alto flujo', 'tipo' => 'Repuestos de Motor', 'precio' => 45.00, 'costo' => 25.00, 'stock' => 40, 'umbral' => 10],
            ['nombre' => 'Pastillas de freno delanteras', 'tipo' => 'Frenos', 'precio' => 55.00, 'costo' => 30.00, 'stock' => 25, 'umbral' => 8],
            ['nombre' => 'Disco de freno 260mm', 'tipo' => 'Frenos', 'precio' => 140.00, 'costo' => 85.00, 'stock' => 6, 'umbral' => 4],
            ['nombre' => 'Bombillo LED H4 (par)', 'tipo' => 'Iluminación', 'precio' => 70.00, 'costo' => 38.00, 'stock' => 3, 'umbral' => 6],
            ['nombre' => 'Casco integral talla M', 'tipo' => 'Cascos y Seguridad', 'precio' => 450.00, 'costo' => 300.00, 'stock' => 10, 'umbral' => 4],
            ['nombre' => 'Guantes de motocross', 'tipo' => 'Cascos y Seguridad', 'precio' => 90.00, 'costo' => 55.00, 'stock' => 18, 'umbral' => 6],
            ['nombre' => 'Espejos retrovisores (par)', 'tipo' => 'Carenados', 'precio' => 35.00, 'costo' => 18.00, 'stock' => 30, 'umbral' => 10],
            ['nombre' => 'Manubrio deportivo', 'tipo' => 'Carenados', 'precio' => 120.00, 'costo' => 70.00, 'stock' => 7, 'umbral' => 4],
            ['nombre' => 'Aceite de motor 20W-50 (litro)', 'tipo' => 'Repuestos de Motor', 'precio' => 32.00, 'costo' => 20.00, 'stock' => 60, 'umbral' => 15],
            ['nombre' => 'Cadena de transmisión 428H', 'tipo' => 'Transmisión', 'precio' => 95.00, 'costo' => 60.00, 'stock' => 4, 'umbral' => 5],
        ];

        foreach ($productos as $p) {
            $categoria = $categorias->firstWhere('nombre', $p['tipo']);
            Producto::firstOrCreate(
                ['nombre' => $p['nombre']],
                [
                    'categoria_id' => $categoria->id,
                    'descripcion' => "Producto de demostración — {$p['nombre']}.",
                    'precio_unitario' => $p['precio'],
                    'costo' => $p['costo'],
                    'stock' => $p['stock'],
                    'umbral_alerta' => $p['umbral'],
                ]
            );
        }

        // Genera alertas para productos que ya están bajo su umbral.
        $inventory = app(InventoryService::class);
        Producto::all()->each(fn (Producto $p) => $inventory->registrarAlertaSiBajaStock($p));

        $clientes = [
            ['nombre' => 'Carlos Mamani', 'email' => 'carlos.mamani@example.com', 'telefono' => '71234567', 'direccion' => 'Av. Arce #123, La Paz'],
            ['nombre' => 'María Quispe', 'email' => 'maria.quispe@example.com', 'telefono' => '79876543', 'direccion' => 'Calle Sagárnaga #45, La Paz'],
            ['nombre' => 'Jorge Vargas', 'email' => 'jorge.vargas@example.com', 'telefono' => '70112233', 'direccion' => 'Zona Sur, Av. Ballivián #890'],
            ['nombre' => 'Ana Condori', 'email' => null, 'telefono' => '76543210', 'direccion' => 'El Alto, Av. Juan Pablo II'],
            ['nombre' => 'Luis Flores', 'email' => 'luis.flores@example.com', 'telefono' => '72345678', 'direccion' => 'Miraflores, Calle 21 #500'],
        ];

        foreach ($clientes as $c) {
            Cliente::firstOrCreate(
                ['nombre' => $c['nombre']],
                $c
            );
        }

        $this->crearVentasDemo();
    }

    private function crearVentasDemo(): void
    {
        $vendedor = User::role('Vendedor')->first()
            ?? User::where('email', 'vendedor@apzmotors.com')->first();

        if ($vendedor === null || Venta::count() > 0) {
            return;
        }

        $ventaService = app(VentaService::class);
        $cliente = Cliente::first();
        $productos = Producto::all();

        $ventas = [
            [[['producto_id' => $productos[0]->id, 'cantidad' => 2]], 200.00, 'Efectivo'],
            [[['producto_id' => $productos[2]->id, 'cantidad' => 1]], 45.00, 'Tarjeta'],
        ];

        foreach ($ventas as [$items, $monto, $metodo]) {
            $ventaService->crearVenta(
                ['cliente_id' => $cliente->id, 'estado' => 'Pagado'],
                $items,
                $vendedor,
                ['monto' => $monto, 'metodo' => $metodo]
            );
        }
    }
}
