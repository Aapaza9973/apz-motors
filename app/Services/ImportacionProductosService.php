<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Importación masiva de productos y categorías desde un archivo CSV
 * (separador `;`, UTF-8, primera fila con encabezados).
 *
 * El análisis valida fila por fila y devuelve un reporte; la importación
 * crea las categorías que falten, registra los movimientos de stock y
 * actualiza los productos existentes (coincidencia por nombre).
 */
class ImportacionProductosService
{
    public const COLUMNAS = [
        'nombre', 'categoria', 'precio_unitario', 'stock',
        'umbral_alerta', 'costo', 'descripcion', 'tipo',
    ];

    public function __construct(private InventoryService $inventory) {}

    /**
     * Analiza el contenido CSV y devuelve las filas con su validación y
     * la acción prevista (crear / actualizar por nombre), para que la
     * vista previa muestre antes de tocar la base si habrá duplicados.
     *
     * @return array{filas: array<int, array{numero: int, datos: array<string, ?string>, errores: array<int, string>, accion: ?string}>}
     */
    public function analizar(string $contenido): array
    {
        // Quita el BOM UTF-8 si el archivo lo trae (Excel lo agrega).
        if (str_starts_with($contenido, "\xEF\xBB\xBF")) {
            $contenido = substr($contenido, 3);
        }

        $lineas = preg_split('/\r\n|\r|\n/', trim($contenido));
        $lineas = array_values(array_filter($lineas, fn ($linea) => trim((string) $linea) !== ''));

        if (empty($lineas)) {
            throw ValidationException::withMessages(['archivo' => 'El archivo está vacío.']);
        }

        $mapa = $this->mapearEncabezados((string) $lineas[0]);

        if (empty($mapa)) {
            throw ValidationException::withMessages([
                'archivo' => 'La primera fila debe ser el encabezado: '.implode(';', self::COLUMNAS),
            ]);
        }

        $filas = [];

        foreach (array_slice($lineas, 1) as $indice => $linea) {
            $numero = $indice + 2; // la fila 1 del archivo es el encabezado
            $celdas = str_getcsv((string) $linea, ';');
            $datos = $this->aDatos($mapa, $celdas);

            if (collect($datos)->every(fn ($valor) => $valor === null || trim((string) $valor) === '')) {
                continue; // fila en blanco
            }

            $errores = $this->validar($datos);

            $filas[] = [
                'numero' => $numero,
                'datos' => $datos,
                'errores' => $errores,
                // Solo tiene sentido mostrar la acción cuando la fila es válida.
                'accion' => empty($errores)
                    ? (Producto::where('nombre', $datos['nombre'])->exists() ? 'actualizar' : 'crear')
                    : null,
            ];
        }

        if (empty($filas)) {
            throw ValidationException::withMessages(['archivo' => 'El archivo no tiene filas de datos después del encabezado.']);
        }

        return ['filas' => $filas];
    }

    /**
     * Importa las filas ya analizadas: crea o actualiza productos y
     * categorías (coincidencia por nombre, sin duplicados) y registra
     * cada movimiento de stock bajo un mismo lote para poder descargar
     * el reporte de esa importación. Las filas con errores se omiten.
     *
     * @param  array<int, array{numero: int, datos: array, errores: array}>  $filas
     * @return array{creados: int, actualizados: int, errores: int, detalle_errores: array<int, string>, lote: string}
     */
    public function importar(array $filas, ?User $usuario): array
    {
        $lote = 'IMP-'.now()->format('Ymd-His');
        $resumen = ['creados' => 0, 'actualizados' => 0, 'errores' => 0, 'detalle_errores' => [], 'lote' => $lote];

        DB::transaction(function () use ($filas, $usuario, $lote, &$resumen) {
            foreach ($filas as $fila) {
                if (! empty($fila['errores'])) {
                    $resumen['errores']++;
                    $resumen['detalle_errores'][] = 'Fila '.$fila['numero'].': '.implode(', ', $fila['errores']).'.';

                    continue;
                }

                $d = $fila['datos'];
                $categoria = Categoria::firstOrCreate(
                    ['nombre' => $d['categoria']],
                    ['tipo' => $d['tipo'] ?? 'Repuesto']
                );

                $datos = [
                    'categoria_id' => $categoria->id,
                    'nombre' => $d['nombre'],
                    'descripcion' => $d['descripcion'] ?: null,
                    'precio_unitario' => $d['precio_unitario'],
                    'costo' => $d['costo'] ?: null,
                    'stock' => (int) $d['stock'],
                    'umbral_alerta' => (int) ($d['umbral_alerta'] ?: 5),
                ];

                $existente = Producto::where('nombre', $d['nombre'])->first();

                if ($existente) {
                    $delta = (int) $d['stock'] - $existente->stock;

                    if ($delta !== 0 && $usuario) {
                        $this->inventory->ajustarStock($existente, $delta, 'ajuste', "Importación masiva (CSV) · lote {$lote}", $usuario);
                    }

                    $existente->update($datos);
                    $resumen['actualizados']++;
                } else {
                    // Nace con stock 0: el stock del CSV entra como movimiento
                    // de entrada para mantener la trazabilidad (evita duplicar).
                    $producto = Producto::create(array_merge($datos, ['stock' => 0]));

                    if ((int) $d['stock'] > 0 && $usuario) {
                        $this->inventory->ajustarStock($producto, (int) $d['stock'], 'entrada', "Importación masiva (CSV) · lote {$lote}", $usuario);
                    }

                    $resumen['creados']++;
                }
            }
        });

        return $resumen;
    }

    /**
     * Movimientos de stock generados por una importación (lote IMP-…).
     * Se filtran por el motivo, que lleva el lote embebido.
     */
    public function movimientosDeLote(string $lote): Collection
    {
        return MovimientoStock::with('producto', 'usuario')
            ->where('motivo', 'like', "%{$lote}%")
            ->orderBy('id')
            ->get();
    }

    /**
     * Mapea los nombres de columna del encabezado a su índice (sin importar
     * el orden ni mayúsculas).
     *
     * @return array<string, int>
     */
    private function mapearEncabezados(string $linea): array
    {
        $mapa = [];

        foreach (str_getcsv($linea, ';') as $indice => $celda) {
            $nombre = strtolower(trim($celda, "\"'\t "));

            if (in_array($nombre, self::COLUMNAS, true)) {
                $mapa[$nombre] = $indice;
            }
        }

        return $mapa;
    }

    /**
     * @param  array<string, int>  $mapa
     * @param  array<int, string>  $celdas
     * @return array<string, ?string>
     */
    private function aDatos(array $mapa, array $celdas): array
    {
        $datos = [];

        foreach (self::COLUMNAS as $columna) {
            $datos[$columna] = isset($mapa[$columna])
                ? trim((string) ($celdas[$mapa[$columna]] ?? ''))
                : null;
        }

        return $datos;
    }

    /**
     * @param  array<string, ?string>  $d
     * @return array<int, string>
     */
    private function validar(array $d): array
    {
        $errores = [];

        if (blank($d['nombre'])) {
            $errores[] = 'falta el nombre';
        } elseif (mb_strlen((string) $d['nombre']) > 255) {
            $errores[] = 'nombre muy largo';
        }

        if (blank($d['categoria'])) {
            $errores[] = 'falta la categoría';
        }

        if (blank($d['precio_unitario']) || ! is_numeric($d['precio_unitario']) || (float) $d['precio_unitario'] <= 0) {
            $errores[] = 'precio inválido (mayor a 0)';
        }

        if (! is_numeric($d['stock']) || (int) $d['stock'] < 0) {
            $errores[] = 'stock inválido (entero ≥ 0)';
        }

        if (! blank($d['umbral_alerta']) && (! is_numeric($d['umbral_alerta']) || (int) $d['umbral_alerta'] < 0)) {
            $errores[] = 'umbral inválido (entero ≥ 0)';
        }

        if (! blank($d['costo']) && (! is_numeric($d['costo']) || (float) $d['costo'] < 0)) {
            $errores[] = 'costo inválido (≥ 0)';
        }

        if (! blank($d['tipo']) && ! in_array($d['tipo'], ['Repuesto', 'Accesorio'], true)) {
            $errores[] = 'tipo inválido (Repuesto o Accesorio)';
        }

        return $errores;
    }
}
