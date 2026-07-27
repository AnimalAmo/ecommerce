<?php

namespace App\Models\Partner;

use App\Support\Phone;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /** Il contatto della candidatura si archivia in E.164 come quello degli utenti. */
    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => Phone::toE164($value));
    }
}
