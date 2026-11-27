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
     * Plusieurs tableaux dans un même CSV (ex. soldes puis mouvements) :
     * chacun avec son titre, séparés par une ligne vide.
     *
     * @param  list<array{title: string, columns: list<array{0: string, 1: string}>, rows: iterable}>  $sheets
     */
    public static function downloadSheets(string $filename, array $sheets): StreamedResponse
    {
        return response()->streamDownload(function () use ($sheets) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            $multi = count($sheets) > 1;

            foreach ($sheets as $n => $sheet) {
                if ($multi) {
                    if ($n > 0) {
                        fwrite($out, "\r\n");
                    }
                    fputcsv($out, [mb_strtoupper($sheet['title'])], ';');
                }

                $types = array_column($sheet['columns'], 1);
                fputcsv($out, array_column($sheet['columns'], 0), ';');

                foreach ($sheet['rows'] as $row) {
                    $cells = [];
                    foreach ($types as $i => $type) {
                        $cells[] = self::cell(self::plain($row[$i] ?? null, $type));
                    }
                    fputcsv($out, $cells, ';');
                }
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Une date devient du texte lisible dans le CSV ; le reste ne change pas. */
    private static function plain(mixed $value, string $type): string|int|float|null
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(match ($type) {
                'time' => 'H:i',
                'datetime' => 'd/m/Y H:i',
                default => 'd/m/Y',
            });
        }

        return $value;
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
