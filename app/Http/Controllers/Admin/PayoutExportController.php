<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order\Order;
use App\Models\OrderPayout\OrderPayout;
use App\Services\Admin\Money\PayoutLedger;
use App\Services\Admin\Money\Period;
use App\Support\Admin\CsvDownload;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Esporta" di Incassi: una riga per riga di registro degli ordini pagati nel
 * periodo, più una per ogni ordine precedente ai pagamenti divisi. La somma
 * della colonna Lordo è l'incassato della pagina.
 *
 * Importi con la virgola e senza separatore delle migliaia: Excel in italiano
 * li legge come numeri.
 */
class PayoutExportController
{
    public function __invoke(Request $request, PayoutLedger $ledger): StreamedResponse
    {
        $key = $request->query('period');
        $period = Period::fromKey(is_string($key) ? $key : null);

        $rows = $ledger->ledgerFor($period)
            ->map(fn (OrderPayout $row): array => [$row->order->created_at, $this->ledgerLine($row, $ledger)])
            ->concat($ledger->unsplitOrdersFor($period)->map(fn (Order $order): array => [$order->created_at, $this->unsplitLine($order)]))
            ->sortBy(fn (array $pair): int => $pair[0]->getTimestamp())
            ->map(fn (array $pair): array => $pair[1]);

        return CsvDownload::make('incassi-'.$period->key.'.csv', [
            __('admin-money.export_file.col_order_date'),
            __('admin-money.export_file.col_order'),
            __('admin-money.export_file.col_item'),
            __('admin-money.export_file.col_partner'),
            __('admin-money.export_file.col_account'),
            __('admin-money.export_file.col_gross'),
            __('admin-money.export_file.col_fee'),
            __('admin-money.export_file.col_net'),
            __('admin-money.export_file.col_net_confirmed'),
            __('admin-money.export_file.col_status'),
            __('admin-money.export_file.col_release_at'),
            __('admin-money.export_file.col_released_at'),
            __('admin-money.export_file.col_payout'),
            __('admin-money.export_file.col_attempts'),
            __('admin-money.export_file.col_error'),
        ], $rows);
    }

    /** @return list<scalar|null> */
    private function ledgerLine(OrderPayout $row, PayoutLedger $ledger): array
    {
        $platform = $row->partner_user_id === null;

        return [
            $this->dateTime($row->order->created_at),
            $row->order->order_number,
            $row->orderItem?->title,
            $platform ? __('admin-money.export_file.platform') : $ledger->partnerLabel($row),
            $row->stripe_account_id,
            $this->amount($row->gross_cents),
            $this->amount($row->commission_cents),
            $this->amount($row->net_cents),
            $platform ? '' : __($row->net_reconciled_at !== null ? 'admin-money.export_file.yes' : 'admin-money.export_file.no'),
            __('admin-money.export_file.status.'.$row->status->value),
            $row->release_at ? $this->date($row->release_at) : '',
            $row->released_at ? $this->date($row->released_at) : '',
            $row->stripe_payout_id,
            $row->payout_attempts ?: '',
            $row->last_error,
        ];
    }

    /** @return list<scalar|null> */
    private function unsplitLine(Order $order): array
    {
        return [
            $this->dateTime($order->created_at),
            $order->order_number,
            '',
            '',
            '',
            $this->amount($order->total_cents),
            '',
            '',
            '',
            __('admin-money.export_file.unsplit'),
            '',
            '',
            '',
            '',
            '',
        ];
    }

    private function amount(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '');
    }

    private function date(CarbonInterface $date): string
    {
        return $date->copy()->setTimezone(Period::timezone())->format('d/m/Y');
    }

    private function dateTime(CarbonInterface $date): string
    {
        return $date->copy()->setTimezone(Period::timezone())->format('d/m/Y H:i');
    }
}
