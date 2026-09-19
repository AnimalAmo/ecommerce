<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Services\Admin\People\UserDirectory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Esporta in Excel" degli iscritti: CSV che Excel apre senza passare dalla
 * procedura di importazione — BOM UTF-8 (accenti), separatore `;` (Excel in
 * italiano), importi con la virgola decimale. Stessi filtri della tabella,
 * letti dalla query string.
 */
class UserExportController
{
    public function __invoke(Request $request, UserDirectory $directory): StreamedResponse
    {
        $query = $directory->query($request->query());

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nome', 'Cognome', 'Email', 'Ruolo', 'Iscritto il', 'Newsletter', 'Ordini pagati', 'Speso (€)', 'Stato'], ';');

            // chunk() e non lazyById(): terrebbe l'ordinamento scelto in tabella.
            $query->chunk(500, fn ($users) => $users->each(function (User $user) use ($out): void {
                fputcsv($out, [
                    self::cell($user->first_name),
                    self::cell($user->last_name),
                    self::cell($user->email),
                    $user->roles->contains('name', 'partner') ? 'Partner' : 'Cliente',
                    $user->created_at?->format('d/m/Y'),
                    match ($user->newsletter_state) {
                        'confirmed' => 'Sì',
                        'pending' => 'In attesa',
                        default => 'No',
                    },
                    (int) $user->paid_orders_count,
                    number_format(((int) $user->spent_cents) / 100, 2, ',', ''),
                    match (true) {
                        $user->anonymized_at !== null => 'Anonimizzato',
                        (bool) $user->is_active => 'Attivo',
                        default => 'Disattivato',
                    },
                ], ';');
            }));

            fclose($out);
        }, 'iscritti-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Testo libero scritto dagli utenti: un nome che comincia con `=` o `+`
     * Excel lo eseguirebbe come formula. L'apostrofo lo tiene testo.
     */
    private static function cell(?string $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true) ? "'".$value : $value;
    }
}
