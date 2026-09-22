<?php

namespace App\Services\Admin\Catalog;

use App\Enums\DraftCompletion;
use App\Exceptions\DraftNotPublishableException;
use App\Exceptions\PartnerServiceException;
use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Admin\People\UserDirectory;
use App\Services\Partner\Publishing\DraftCompleter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * L'admin crea una scheda al posto del partner (spec §5). Non entra come lui
 * e non passa dal wizard: quelle pagine stanno dietro al middleware `partner`
 * e InteractsWithStructureDraft intesterebbe la bozza al superadmin.
 *
 * La scheda però deve nascere identica: stessa bozza, stessa chiusura
 * (DraftCompleter, l'unico modo di chiudere una bozza), stesso publisher.
 * Il partner la ritrova in "I miei servizi" e la modifica col suo percorso.
 *
 * Tutto dentro una transazione perché i tre passi sono un'operazione sola:
 * senza, una bozza rifiutata da DraftPublisher resterebbe `draft` a nome del
 * partner, invisibile ovunque e impossibile da riprendere. Le foto non stanno
 * nel database e vanno pulite a mano, nel catch.
 *
 * L'esito "in attesa" (partner online che non ha ancora collegato Stripe) non
 * è un errore e non si pulisce niente: la bozza e le sue foto sono quelle che
 * PublishAwaitingDrafts manderà a catalogo da sé (P4).
 */
class AdminServiceCreator
{
    /**
     * Famiglie creabili dal pannello, nell'ordine in cui compaiono nel menù.
     * È l'unico elenco: lo usano la rotta (`whereIn`), il menù della scheda
     * partner, la modale del catalogo e il data provider di AdminAccessTest.
     *
     * `structure` e `service` sono lo stesso componente con `service_category`
     * diversa: sul B2C la seconda esce comunque come struttura a notte, che è
     * un difetto noto del publisher e non una scelta dell'admin.
     */
    public const CREATABLE_FAMILIES = ['structure', 'service', 'activity', 'smartbox'];

    public function __construct(
        private readonly DraftCompleter $completer,
        private readonly UserDirectory $directory,
    ) {}

    /**
     * @param  array<string, mixed>  $draftAttributes  campi della bozza già validati (senza user_id, categoria, stato e foto)
     * @param  list<string>  $photoPaths  foto già memorizzate sul disco public, la prima è la copertina
     * @param  StructureDraft|null  $draft  esce valorizzato con la bozza creata: serve al chiamante per trovare la riga a catalogo (structure_draft_id) e costruire il redirect
     *
     * @throws PartnerServiceException il partner non è ricevibile
     * @throws DraftNotPublishableException mancano i dati minimi per il catalogo
     */
    public function create(
        User $partner,
        string $serviceCategory,
        array $draftAttributes,
        array $photoPaths,
        ?StructureDraft &$draft = null,
    ): DraftCompletion {
        $draft = null;

        if (! $this->isEligible($partner)) {
            Storage::disk('public')->delete($photoPaths);

            throw PartnerServiceException::notEligible($partner);
        }

        try {
            return DB::transaction(function () use ($partner, $serviceCategory, $draftAttributes, $photoPaths, &$draft): DraftCompletion {
                $draft = StructureDraft::create([
                    ...$draftAttributes,
                    'user_id' => $partner->id,
                    'service_category' => $serviceCategory,
                    // Nasce `draft` e allo step zero: è DraftCompleter a
                    // portarla a `completed` e allo step finale, così la
                    // bozza non è mai "chiusa" senza una riga a catalogo.
                    'status' => StructureDraft::STATUS_DRAFT,
                    'current_step' => 0,
                    'photos' => $photoPaths,
                ]);

                $outcome = $this->completer->complete($draft, $draft->finalStep());

                if ($outcome === DraftCompletion::Published) {
                    $this->approveWithoutNotice($draft);
                }

                return $outcome;
            });
        } catch (Throwable $e) {
            Storage::disk('public')->delete($photoPaths);
            $draft = null;

            throw $e;
        }
    }

    /**
     * Partner a cui il pannello può intestare una scheda: gli stessi che
     * `create()` accetta, così il select non offre nomi che poi rifiuta.
     * La query è quella dell'elenco "Iscritti" (ruolo partner, attivi, non
     * anonimizzati, superadmin esclusi): un filtro solo, in un posto solo.
     *
     * Torna STRINGHE, non model: la vista ci fa `@foreach ($eligible as $id
     * => $label)`.
     *
     * @return Collection<int, string> id => ragione sociale, o nome e cognome
     */
    public function eligiblePartners(): Collection
    {
        return $this->directory
            ->query(['role' => 'partner', 'status' => 'active', 'sort' => 'name', 'dir' => 'asc'])
            ->whereHas('partnerProfile')
            ->with('partnerProfile')
            ->get()
            ->mapWithKeys(fn (User $partner): array => [
                $partner->id => $partner->partnerProfile?->business_name ?: $partner->name,
            ]);
    }

    /**
     * Riga di catalogo nata dalla bozza. DraftCompleter torna solo l'esito, e
     * l'unica chiave stabile fra bozza e catalogo è `structure_draft_id`
     * (unique sulle tre tabelle). `withHidden` perché con la moderazione
     * accesa la riga appena creata è ancora fuori dallo scope di visibilità.
     */
    public function publishedRow(StructureDraft $draft): ?Model
    {
        return match ($draft->family()) {
            'attivita' => Event::withHidden()->firstWhere('structure_draft_id', $draft->id),
            'smartbox' => SmartboxPackage::withHidden()->firstWhere('structure_draft_id', $draft->id),
            default => Structure::withHidden()->firstWhere('structure_draft_id', $draft->id),
        };
    }

    /** Alias di `admin.catalog.show` per la famiglia della bozza. */
    public function catalogType(StructureDraft $draft): string
    {
        return match ($draft->family()) {
            'attivita' => 'event',
            'smartbox' => 'smartbox_package',
            default => 'structure',
        };
    }

    /**
     * Una scheda che ha scritto l'admin non ha niente da far approvare
     * all'admin: con `ADMIN_MODERATION` accesa il publisher la metterebbe
     * "in attesa" e la lascerebbe fuori dal sito, in coda a se stessa.
     * Si approva subito e in silenzio — nessuna mail al partner, che di
     * questa scheda non ha mai chiesto niente.
     */
    private function approveWithoutNotice(StructureDraft $draft): void
    {
        if (! config('admin.moderation')) {
            // Moderazione spenta: la colonna nasce già `approved` dal default.
            return;
        }

        $this->publishedRow($draft)?->forceFill([
            'approval_status' => Structure::APPROVAL_APPROVED,
            'approved_at' => now(),
            'approval_requested_at' => null,
            'approval_note' => null,
        ])->save();
    }

    /** Senza profilo non si sa nemmeno come il partner verrebbe pagato: è il controllo di DraftPublisher, anticipato. */
    private function isEligible(User $partner): bool
    {
        return $partner->hasRole('partner')
            && (bool) $partner->is_active
            && $partner->anonymized_at === null
            && $partner->partnerProfile !== null;
    }
}
