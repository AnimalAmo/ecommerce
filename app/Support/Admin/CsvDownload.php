<?php

namespace App\Support\Admin;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV che Excel apre bene su un PC italiano: BOM UTF-8 (senza, le lettere
 * accentate escono rotte) e punto e virgola come separatore (la virgola è il
 * separatore decimale, Excel in italiano non spezzerebbe le colonne).
 */
final class CsvDownload
{
    /**
     * @param  list<string>  $header
     * @param  iterable<array<int, scalar|null>>  $rows
     */
    public static function make(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header, ';', escape: '');

            foreach ($rows as $row) {
                fputcsv($out, array_map(self::cell(...), $row), ';', escape: '');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Una cella che inizia con = + - @ Excel la esegue come formula: un nome
     * scritto da un utente del sito non deve poter diventare codice sul PC di
     * chi esporta (CSV injection). L'apostrofo la rende testo.
     */
    private static function cell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }
}
