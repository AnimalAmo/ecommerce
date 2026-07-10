<?php

namespace App\Models\Structure;

use Illuminate\Database\Eloquent\Model;

/**
 * Bozza di onboarding di una struttura ricettiva (wizard partner, 11 step).
 * Resta `draft` finché l'utente non completa lo step 11 (metodo di pagamento),
 * così uno stato parziale è sempre salvato se interrompe.
 */
class StructureDraft extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_COMPLETED = 'completed';

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
        'rooms',
        'checkin_from',
        'checkin_to',
        'checkout_from',
        'checkout_to',
        'cancellation_when',
        'services',
        'additional_services',
        'additional_other',
        'meal_times',
        'rules',
        'animal_services',
        'animal_services_other',
        'smartbox_consent',
        'smartbox_types',
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
            'date_start' => 'date',
            'date_end' => 'date',
            'rooms' => 'array',
            'meal_times' => 'array',
            'services' => 'array',
            'additional_services' => 'array',
            'rules' => 'array',
            'animal_services' => 'array',
            'smartbox_types' => 'array',
            'photos' => 'array',
        ];
    }
}
