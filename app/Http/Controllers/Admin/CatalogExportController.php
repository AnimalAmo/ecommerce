<?php

namespace App\Http\Controllers\Admin;

use App\Services\Admin\Catalog\CatalogAdmin;
use App\Services\Admin\Catalog\CatalogPresenter;
use App\Support\Admin\CsvDownload;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** "Esporta" del catalogo: le schede con gli stessi filtri dell'elenco. */
class CatalogExportController
{
    public function __invoke(Request $request, CatalogAdmin $catalog, CatalogPresenter $presenter): StreamedResponse
    {
        $filters = $request->only(['search', 'partner', 'family', 'region', 'status']);

        $rows = $catalog->all($filters)->map(function ($item) use ($presenter, $catalog): array {
            $row = $presenter->row($item);

            return [
                $row['name'],
                $row['type'],
                $row['partner'],
                $row['place'],
                $row['region'] ?? '',
                $row['price'],
                $row['statusLabel'],
                $catalog->bookings($item)['total'],
                $item->created_at?->format('d/m/Y'),
            ];
        });

        return CsvDownload::make(
            __('admin-catalog.export.filename', ['date' => now()->format('Y-m-d')]),
            array_values(__('admin-catalog.export.columns')),
            $rows,
        );
    }
}
