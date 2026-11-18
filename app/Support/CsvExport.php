<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Fichier CSV qui s'ouvre directement dans Excel en français :
 * séparateur « ; », encodage UTF-8 avec BOM (accents corrects),
 * montants en nombres entiers (calculables).
 */
class CsvExport
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<array<int, string|int|null>>  $rows
     */
    public static function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM : Excel lit l'UTF-8

            fputcsv($out, $headers, ';');

            foreach ($rows as $row) {
                fputcsv($out, array_map([self::class, 'cell'], $row), ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Protège contre l'injection de formules : un texte saisi par quelqu'un
     * (nom de client, note…) qui commence par = + - @ serait exécuté par Excel.
     */
    public static function cell(string|int|float|null $value): string|int|float
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
