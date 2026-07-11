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
 * attività/eventi 10, smartbox 12). Resta `draft` finché l'utente non completa
 * l'ultimo step del flusso, così uno stato parziale è sempre salvato se interrompe.
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
        'sdi',
        'bic',
    ];

    protected function casts(): array
    {
        return [
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

    /** Servizi completati dell'utente, dal più recente. */
    public function scopeCompletedFor(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId)
            ->where('status', self::STATUS_COMPLETED)
            ->latest();
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
