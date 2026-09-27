<?php

use App\Models\Partner\PartnerProfile;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\StructureDraft;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Ritira dalla vetrina le smartbox già pubblicate il cui partner non può
     * più pubblicarne.
     *
     * Richiesta della cliente del 27/09/2026: «una Smartbox non deve poter
     * essere pubblicata/acquistata se il partner non ha collegato un sistema di
     * pagamento», «non la terrei in vetrina se non è acquistabile». Il gate
     * nuovo vale da qui in avanti; i cofanetti messi in vetrina prima restano
     * acquistabili finché qualcuno non li ritira, e questo è quel qualcuno.
     *
     * Reversibile dal percorso normale: sulla bozza si scrive
     * `publish_requested_at`, così quando il partner torna all'incasso online
     * PartnerPaymentModeService ripubblica e SmartboxPublisher azzera
     * `withheld_at`. Niente mail al partner: bastano il badge in "I miei
     * servizi" e l'avviso in dashboard.
     *
     * Idempotente: si guardano solo le righe con `withheld_at` nullo e si
     * scrive il segnale solo dove non c'è già, quindi rilanciarla dopo
     * un'interruzione riprende da dove era.
     *
     * Restano fuori le smartbox del catalogo demo (`user_id` nullo, da
     * SmartboxPackageSeeder): non hanno un partner che incassa, e chi le
     * governa è `animalamo:purge-mock-catalog`.
     */
    public function up(): void
    {
        $partnerIds = SmartboxPackage::withHidden()
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id): int => (int) $id);

        if ($partnerIds->isEmpty()) {
            return;
        }

        $profiles = PartnerProfile::query()
            ->whereIn('user_id', $partnerIds)
            ->get()
            ->keyBy(fn (PartnerProfile $profile): int => (int) $profile->user_id);

        // Senza profilo il partner non passa nemmeno il controllo di
        // DraftPublisher: si ritira, come si ritira chi non incassa online.
        $blocked = $partnerIds
            ->filter(fn (int $id): bool => $profiles->get($id)?->canPublishFamily('smartbox') !== true)
            ->values();

        if ($blocked->isEmpty()) {
            return;
        }

        $rows = SmartboxPackage::withHidden()
            ->whereIn('user_id', $blocked)
            ->whereNull('withheld_at')
            ->get(['id', 'slug', 'user_id', 'structure_draft_id']);

        if ($rows->isEmpty()) {
            return;
        }

        SmartboxPackage::withHidden()
            ->whereIn('id', $rows->pluck('id'))
            ->update(['withheld_at' => now()]);

        $draftIds = $rows->pluck('structure_draft_id')->filter()->values();

        if ($draftIds->isNotEmpty()) {
            StructureDraft::query()
                ->whereIn('id', $draftIds)
                ->whereNull('publish_requested_at')
                ->update(['publish_requested_at' => now()]);
        }

        $this->reportRowsWithoutDraft($rows);
    }

    /**
     * Vuoto di proposito: è una migrazione-dati, e il ritiro si annulla dal
     * percorso normale (il partner torna all'incasso online e la scheda viene
     * ripubblicata). Un `down()` che azzerasse `withheld_at` rimetterebbe in
     * vetrina cofanetti non acquistabili, cioè proprio quello che la cliente ha
     * chiesto di evitare.
     */
    public function down(): void {}

    /**
     * Righe senza bozza: sono le smartbox della finestra in cui il wizard era
     * aperto agli ospiti (fino all'08/09/2026). Vengono ritirate come le altre,
     * ma non hanno un `publish_requested_at` su cui ripartire: il ritorno del
     * partner all'incasso online non le ripubblicherà, e andranno riprese a
     * mano. Si lascia l'elenco nel log, perché è l'unico momento in cui si sa
     * quali sono.
     *
     * @param  EloquentCollection<int, SmartboxPackage>  $rows
     */
    private function reportRowsWithoutDraft(EloquentCollection $rows): void
    {
        $orphans = $rows->whereNull('structure_draft_id');

        if ($orphans->isEmpty()) {
            return;
        }

        Log::warning('Smartbox ritirate senza bozza: da riprendere a mano', [
            'smartbox_package_ids' => $orphans->pluck('id')->all(),
            'slugs' => $orphans->pluck('slug')->all(),
            'partner_user_ids' => $orphans->pluck('user_id')->unique()->values()->all(),
        ]);
    }
};
