<?php

namespace App\Models\Partner;

use Illuminate\Database\Eloquent\Model;

/**
 * Candidatura "Lavora con noi" (form pubblico B2C → invito via email al
 * form a step dell'iscrizione B2B).
 */
class PartnerApplication extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_INVITED = 'invited';

    public const STATUS_REGISTERED = 'registered';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'website',
        'city',
        'business_name',
        'role',
        'offer_type',
        'description',
        'status',
        'invited_at',
        'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'invited_at' => 'datetime',
            'registered_at' => 'datetime',
        ];
    }
}
