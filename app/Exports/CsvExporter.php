<?php

namespace App\Exports;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Genera respuestas CSV listas para descargar (compatible con Excel).
 */
class CsvExporter
{
    /**
     * @param  array<int, string>  $encabezados
     * @param  iterable<int, array<int, mixed>>  $filas
     */
    public static function stream(string $nombreArchivo, array $encabezados, iterable $filas): StreamedResponse
    {
        return response()->streamDownload(function () use ($encabezados, $filas) {
            $salida = fopen('php://output', 'w');

            // BOM UTF-8 para que Excel interprete bien los acentos.
            fwrite($salida, "\xEF\xBB\xBF");

            fputcsv($salida, $encabezados, ';');

            foreach ($filas as $fila) {
                fputcsv($salida, $fila, ';');
            }

            fclose($salida);
        }, $nombreArchivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
