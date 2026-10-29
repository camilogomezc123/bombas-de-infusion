<?php

namespace App\Support;

use App\Services\ClinicalAuditService;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exportación CSV nativa (fputcsv), sin dependencias externas y sin cola —
 * se descarga de inmediato, apta para los volúmenes de datos de un programa institucional.
 * Registra cada exportación en la auditoría (quién exportó qué y cuántas filas), porque
 * el archivo puede contener datos identificables de pacientes.
 */
class CsvExporter
{
    /**
     * @param  array<int, string>  $headers
     * @param  iterable<array<int, mixed>>  $rows
     */
    public static function stream(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        $rows = $rows instanceof Collection ? $rows : collect($rows);

        self::audit($filename, $rows->count());

        return new StreamedResponse(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM para acentos en Excel
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private static function audit(string $filename, int $rowCount): void
    {
        $program = ProgramAccess::program();

        if (! $program) {
            return;
        }

        app(ClinicalAuditService::class)->record(
            $program,
            $program->id,
            'exported',
            'csv_export',
            null,
            "{$filename} ({$rowCount} filas)",
        );
    }
}
