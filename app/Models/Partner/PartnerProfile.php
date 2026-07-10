<?php

namespace App\Models\Partner;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Profilo B2B (1:1 con {@see User} di ruolo partner): dati fiscali + coordinate
 * di pagamento mostrati/modificati nelle pagine Profilo del partner.
 */
class PartnerProfile extends Model
{
    protected $fillable = [
        'business_name',
        'vat',
        'tax_code',
        'pec',
        'sdi',
        'address',
        'city',
        'province',
        'zip',
        'account_holder',
        'iban',
        'bic',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
