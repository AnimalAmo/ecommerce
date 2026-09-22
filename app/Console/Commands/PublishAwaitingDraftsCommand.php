<?php

namespace App\Console\Commands;

use App\Models\Partner\PartnerProfile;
use App\Models\Structure\StructureDraft;
use App\Services\Partner\Publishing\AwaitingDraftPublisher;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Rete di sicurezza della pubblicazione automatica (P4). Il job
 * PublishAwaitingDrafts parte da account.updated e dal cambio di modalità, ma
 * un worker non è garantito in produzione (vedi ResetPasswordMail) e un
 * webhook può non arrivare. Ogni dieci minuti si pubblicano in sincrono le
 * bozze in attesa dei partner attivi che ora possono pubblicare.
 *
 * Si saltano le bozze senza proprietario (finestra da ospite prima
 * dell'08/09/2026), perché non c'è un partner che le venda, e quelle dei
 * partner disattivati o anonimizzati. AwaitingDraftPublisher ripete comunque
 * il controllo per ogni bozza. Un partner che fallisce non ferma gli altri.
 */
class PublishAwaitingDraftsCommand extends Command
{
    protected $signature = 'animalamo:publish-awaiting-drafts';

    protected $description = 'Pubblica i servizi in attesa di Stripe dei partner che ora possono pubblicare';

    public function handle(AwaitingDraftPublisher $publisher): int
    {
        $partnerIds = StructureDraft::query()
            ->awaitingPublication()
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $published = 0;

        PartnerProfile::query()
            ->whereIn('user_id', $partnerIds)
            ->whereHas('user', fn (Builder $query): Builder => $query->where('is_active', true))
            ->orderBy('user_id')
            ->get()
            ->filter(fn (PartnerProfile $profile): bool => $profile->canPublish())
            ->each(function (PartnerProfile $profile) use ($publisher, &$published): void {
                try {
                    $published += $publisher->publishFor((int) $profile->user_id);
                } catch (Throwable $exception) {
                    Log::warning('Pubblicazione delle bozze in attesa fallita', [
                        'partner_user_id' => $profile->user_id,
                        'error' => $exception->getMessage(),
                    ]);
                    $this->components->warn("Partner {$profile->user_id}: {$exception->getMessage()}");
                }
            });

        $this->components->info("Bozze pubblicate: {$published}.");

        return self::SUCCESS;
    }
}
