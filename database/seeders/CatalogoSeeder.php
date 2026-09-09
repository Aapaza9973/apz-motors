<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;

/**
 * Amplía el catálogo con productos reales de la tienda (repuestos,
 * accesorios, iluminación, eléctricos y más) para que estén disponibles
 * en el catálogo público desde el primer día.
 *
 * Es idempotente: usa firstOrCreate por nombre y registra el stock
 * inicial como movimiento de entrada (trazable como el resto del sistema).
 */
class CatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = collect([
            ['nombre' => 'Eléctricos', 'tipo' => 'Repuesto'],
            ['nombre' => 'Accesorios', 'tipo' => 'Accesorio'],
        ])->map(fn ($c) => Categoria::firstOrCreate(['nombre' => $c['nombre']], $c));

        $productos = [
            // Repuestos de motor y filtros
            ['nombre' => 'Filtro de aceite universal', 'categoria' => 'Repuestos de Motor', 'precio' => 25.00, 'costo' => 14.00, 'stock' => 35, 'umbral' => 10, 'descripcion' => 'Filtro de aceite compatible con motos 125cc a 200cc.'],
            ['nombre' => 'Kit de pistón y aros 150cc', 'categoria' => 'Repuestos de Motor', 'precio' => 210.00, 'costo' => 140.00, 'stock' => 9, 'umbral' => 4, 'descripcion' => 'Pistón completo con aros y bulón para motores 150cc.'],
            ['nombre' => 'Cárter de aceite con tapa', 'categoria' => 'Repuestos de Motor', 'precio' => 165.00, 'costo' => 100.00, 'stock' => 5, 'umbral' => 3, 'descripcion' => 'Cárter de aluminio con tapa de drenaje hermética.'],
            ['nombre' => 'Empaquetadura de tapa de cilindro', 'categoria' => 'Repuestos de Motor', 'precio' => 15.00, 'costo' => 7.00, 'stock' => 50, 'umbral' => 15, 'descripcion' => 'Empaquetadura resistente a altas temperaturas.'],
            ['nombre' => 'Variador CVT completo', 'categoria' => 'Repuestos de Motor', 'precio' => 190.00, 'costo' => 125.00, 'stock' => 7, 'umbral' => 3, 'descripcion' => 'Variador continuo para scooters y automaticas 125cc-150cc.'],
            ['nombre' => 'Correa de transmisión CVT', 'categoria' => 'Repuestos de Motor', 'precio' => 65.00, 'costo' => 38.00, 'stock' => 20, 'umbral' => 8, 'descripcion' => 'Correa reforzada para transmisión automática.'],

            // Encendido y eléctricos
            ['nombre' => 'Bujía iridio NGK', 'categoria' => 'Encendido', 'precio' => 35.00, 'costo' => 20.00, 'stock' => 45, 'umbral' => 12, 'descripcion' => 'Bujía de iridio de larga duración, mejor arranque en frío.'],
            ['nombre' => 'Bobina de encendido HT', 'categoria' => 'Encendido', 'precio' => 55.00, 'costo' => 32.00, 'stock' => 14, 'umbral' => 5, 'descripcion' => 'Bobina de alto voltaje para sistemas CDI.'],
            ['nombre' => 'CDI racing 6 pines', 'categoria' => 'Encendido', 'precio' => 95.00, 'costo' => 58.00, 'stock' => 11, 'umbral' => 4, 'descripcion' => 'CDI sin limitador de revoluciones para 125cc-200cc.'],
            ['nombre' => 'Regulador de voltaje 12V', 'categoria' => 'Eléctricos', 'precio' => 60.00, 'costo' => 35.00, 'stock' => 16, 'umbral' => 6, 'descripcion' => 'Regulador-rectificador para sistemas de carga 12V.'],
            ['nombre' => 'Estator de magneto completo', 'categoria' => 'Eléctricos', 'precio' => 180.00, 'costo' => 115.00, 'stock' => 6, 'umbral' => 3, 'descripcion' => 'Estator con bobinado de carga y sensor de encendido.'],
            ['nombre' => 'Motor de arranque 12V', 'categoria' => 'Eléctricos', 'precio' => 240.00, 'costo' => 160.00, 'stock' => 4, 'umbral' => 3, 'descripcion' => 'Marcha para motos con arranque eléctrico 125cc-250cc.'],
            ['nombre' => 'Switch de luces y bocina', 'categoria' => 'Eléctricos', 'precio' => 40.00, 'costo' => 22.00, 'stock' => 22, 'umbral' => 8, 'descripcion' => 'Comando izquierdo con luces, giros y bocina.'],
            ['nombre' => 'Bocina 12V antirruido', 'categoria' => 'Eléctricos', 'precio' => 30.00, 'costo' => 16.00, 'stock' => 28, 'umbral' => 10, 'descripcion' => 'Bocina de disco con alta audibilidad urbana.'],
            ['nombre' => 'Relé de arranque 12V', 'categoria' => 'Eléctricos', 'precio' => 28.00, 'costo' => 15.00, 'stock' => 3, 'umbral' => 5, 'descripcion' => 'Relé (solenoide) de marcha para sistemas 12V.'],
            ['nombre' => 'Fusibles de repuesto (pack x10)', 'categoria' => 'Eléctricos', 'precio' => 12.00, 'costo' => 5.00, 'stock' => 60, 'umbral' => 20, 'descripcion' => 'Pack de 10 fusibles de 7.5A a 20A.'],

            // Iluminación
            ['nombre' => 'Faró LED doble ojo', 'categoria' => 'Iluminación', 'precio' => 120.00, 'costo' => 75.00, 'stock' => 13, 'umbral' => 5, 'descripcion' => 'Optica LED auxiliar con dos ojos para calle.'],
            ['nombre' => 'Luces traseras LED integradas', 'categoria' => 'Iluminación', 'precio' => 85.00, 'costo' => 50.00, 'stock' => 18, 'umbral' => 6, 'descripcion' => 'Stop LED con giro integrado y luz de placa.'],
            ['nombre' => 'Bombillo halógeno H4 35W', 'categoria' => 'Iluminación', 'precio' => 20.00, 'costo' => 10.00, 'stock' => 40, 'umbral' => 12, 'descripcion' => 'Bombillo halógeno estándar de 35W.'],
            ['nombre' => 'Barra LED auxiliar 18W', 'categoria' => 'Iluminación', 'precio' => 95.00, 'costo' => 60.00, 'stock' => 2, 'umbral' => 5, 'descripcion' => 'Barra de luz auxiliar para viaje nocturno.'],
            ['nombre' => 'Guiñadores LED universales', 'categoria' => 'Iluminación', 'precio' => 25.00, 'costo' => 12.00, 'stock' => 34, 'umbral' => 10, 'descripcion' => 'Juego de 4 guiñadores LED con resistencias incluidas.'],

            // Frenos y transmisión
            ['nombre' => 'Disco de freno trasero 220mm', 'categoria' => 'Frenos', 'precio' => 130.00, 'costo' => 82.00, 'stock' => 8, 'umbral' => 4, 'descripcion' => 'Disco ventilado trasero de acero inoxidable.'],
            ['nombre' => 'Bomba de freno delantera', 'categoria' => 'Frenos', 'precio' => 110.00, 'costo' => 70.00, 'stock' => 10, 'umbral' => 4, 'descripcion' => 'Bomba hidráulica con palanca y espejo incluido.'],
            ['nombre' => 'Kit de caliper con pastillas', 'categoria' => 'Frenos', 'precio' => 150.00, 'costo' => 95.00, 'stock' => 6, 'umbral' => 3, 'descripcion' => 'Caliper completo con pastillas semi-metálicas.'],
            ['nombre' => 'Cable de embrague reforzado', 'categoria' => 'Transmisión', 'precio' => 22.00, 'costo' => 11.00, 'stock' => 30, 'umbral' => 10, 'descripcion' => 'Cable de acero teflonado para embrague manual.'],
            ['nombre' => 'Piñón de salida 14T 428', 'categoria' => 'Transmisión', 'precio' => 45.00, 'costo' => 26.00, 'stock' => 15, 'umbral' => 6, 'descripcion' => 'Piñón frontal de 14 dientes para cadena 428.'],
            ['nombre' => 'Corona 428 43 dientes', 'categoria' => 'Transmisión', 'precio' => 55.00, 'costo' => 32.00, 'stock' => 17, 'umbral' => 6, 'descripcion' => 'Corona trasera de acero templado, 43 dientes.'],

            // Accesorios
            ['nombre' => 'Cubre motor de acero', 'categoria' => 'Accesorios', 'precio' => 75.00, 'costo' => 45.00, 'stock' => 12, 'umbral' => 5, 'descripcion' => 'Protector de cárter en acero reforzado.'],
            ['nombre' => 'Portaequipaje trasero universal', 'categoria' => 'Accesorios', 'precio' => 90.00, 'costo' => 55.00, 'stock' => 9, 'umbral' => 4, 'descripcion' => 'Parrilla trasera compatible con baules de 30L-45L.'],
            ['nombre' => 'Baulín 30L con cerradura', 'categoria' => 'Accesorios', 'precio' => 130.00, 'costo' => 80.00, 'stock' => 11, 'umbral' => 4, 'descripcion' => 'Baúl trasero con cerradura y base universal.'],
            ['nombre' => 'Cubellones (sujetaequipaje)', 'categoria' => 'Accesorios', 'precio' => 18.00, 'costo' => 8.00, 'stock' => 48, 'umbral' => 15, 'descripcion' => 'Juego de gomas elásticas con ganchos de acero.'],
        ];

        $usuario = User::role('Admin')->first();
        $inventory = app(InventoryService::class);

        foreach ($productos as $p) {
            $categoria = Categoria::where('nombre', $p['categoria'])->firstOrFail();

            $producto = Producto::firstOrCreate(
                ['nombre' => $p['nombre']],
                [
                    'categoria_id' => $categoria->id,
                    'descripcion' => $p['descripcion'],
                    'precio_unitario' => $p['precio'],
                    'costo' => $p['costo'],
                    'stock' => 0,
                    'umbral_alerta' => $p['umbral'],
                ]
            );

            if ($producto->wasRecentlyCreated && $p['stock'] > 0) {
                $inventory->ajustarStock($producto, $p['stock'], 'entrada', 'Carga inicial del catálogo', $usuario);
            }
        }

        // Alertas para los que nacieron bajo su umbral.
        Producto::all()->each(fn (Producto $p) => $inventory->registrarAlertaSiBajaStock($p));
    }
}
