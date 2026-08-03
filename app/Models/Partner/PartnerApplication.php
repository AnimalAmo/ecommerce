<?php

namespace App\Models\Partner;

use App\Models\User;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Candidatura "Lavora con noi" (form pubblico B2C → invito via email al
 * form a step dell'iscrizione B2B). Se a candidarsi è un utente già
 * registrato sull'ecommerce la riga porta anche il suo user_id.
 */
class PartnerApplication extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_INVITED = 'invited';

    public const STATUS_REGISTERED = 'registered';

    protected $fillable = [
        'user_id',
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

    /** Account B2C che ha inviato la richiesta (null per le candidature da visitatore). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Candidature ancora da trasformare in account partner. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_INVITED]);
    }
}
