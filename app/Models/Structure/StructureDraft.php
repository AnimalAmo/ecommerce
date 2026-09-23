<?php

namespace App\Models\Structure;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

/**
 * Bozza di onboarding di un servizio partner (wizard multi-step: hotel 11,
 * attività/eventi 10 step ma chiusura a 11, smartbox 12). Resta `draft` finché
 * l'utente non completa l'ultimo step del flusso, così uno stato parziale è
 * sempre salvato se interrompe. `publish_requested_at` valorizzato = il partner
 * l'ha chiusa quando non poteva ancora essere pagato: la pubblica
 * AwaitingDraftPublisher appena Stripe è collegato (P4).
 */
class StructureDraft extends Model
{
    use HasTranslations;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_COMPLETED = 'completed';

    /**
     * Testi liberi del partner, localizzati it/en (spatie/laravel-translatable,
     * JSON in colonna). Un valore stringa assegnato finisce sul locale corrente,
     * quindi gli step non ancora convertiti ai tab lingua restano compatibili.
     */
    public array $translatable = [
        'name',
        'description',
        'detailed_description',
        'meeting_point',
        'additional_other',
        'animal_services_other',
    ];

    protected $fillable = [
        'user_id',
        'status',
        'publish_requested_at',
        'current_step',
        'service_category',
        'type',
        'name',
        'address',
        'city',
        'province',
        'zip',
        'license',
        'meeting_point',
        'description',
        'detailed_description',
        'date_start',
        'date_end',
        'time_start',
        'time_end',
        'price_type',
        'price_per_person',
        'price',
        'duration_days',
        'rooms',
        'checkin_from',
        'checkin_to',
        'checkout_from',
        'checkout_to',
        'cancellation_when',
        'services',
        'additional_services',
        'additional_other',
        'included_services',
        'meal_times',
        'meals',
        'dietary_restrictions',
        'rules',
        'animal_services',
        'animal_services_other',
        'smartbox_consent',
        'smartbox_types',
        'smartbox_structures',
        'photos',
        'account_holder',
        'iban',
        'bic',
    ];

    protected function casts(): array
    {
        return [
            'publish_requested_at' => 'datetime',
            'current_step' => 'integer',
            'duration_days' => 'integer',
            'date_start' => 'date',
            'date_end' => 'date',
            'rooms' => 'array',
            'meal_times' => 'array',
            'meals' => 'array',
            'dietary_restrictions' => 'array',
            'services' => 'array',
            'additional_services' => 'array',
            'included_services' => 'array',
            'rules' => 'array',
            'animal_services' => 'array',
            'smartbox_types' => 'array',
            'smartbox_structures' => 'array',
            'photos' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Bozze chiuse dal partner e ferme in attesa che possa pubblicare (P4). */
    public function scopeAwaitingPublication(Builder $query): Builder
    {
        return $query->whereNotNull('publish_requested_at');
    }

    /**
     * Ciò che "I miei servizi" mostra, apre, modifica ed elimina: i servizi
     * completati e quelli in attesa di Stripe. Una sola scope per lista,
     * modifica, eliminazione e dettaglio, così i quattro punti non divergono.
     */
    public function scopeListableFor(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId)
            ->where(fn (Builder $query): Builder => $query
                ->where('status', self::STATUS_COMPLETED)
                ->orWhereNotNull('publish_requested_at'))
            ->latest();
    }

    public function isAwaitingPublication(): bool
    {
        return $this->publish_requested_at !== null;
    }

    /**
     * Step con cui il wizard chiude la famiglia. Le attività hanno 10 step ma
     * da sempre si chiudono a 11 (default di completeDraft, verificato da
     * PartnerActivityCancellationTest): si resta su 11 perché è ciò che è già
     * scritto nelle bozze esistenti.
     */
    public function finalStep(): int
    {
        return match ($this->family()) {
            'smartbox' => 12,
            default => 11,
        };
    }

    /** URL pubblico della prima foto caricata, o null. */
    public function coverPhotoUrl(): ?string
    {
        $first = $this->photos[0] ?? null;

        return $first ? Storage::disk('public')->url($first) : null;
    }

    /** Etichetta luogo per le card ("Città (PROV), Italia"), con fallback. */
    public function locationLabel(): string
    {
        $place = trim($this->city.' '.($this->province ? "({$this->province})" : ''));

        return $place !== '' ? $place.', Italia' : (string) $this->address;
    }

    /** Chiave famiglia servizio: struttura | attivita | smartbox (fallback su service_category). */
    public function family(): string
    {
        return match ($this->service_category) {
            'attivita' => 'attivita',
            'smartbox' => 'smartbox',
            default => 'struttura',
        };
    }
}
