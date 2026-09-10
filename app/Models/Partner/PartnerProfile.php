<?php

namespace App\Models\Partner;

use App\Models\User;
use Database\Factories\Partner\PartnerProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Profilo B2B (1:1 con {@see User} di ruolo partner): dati fiscali + coordinate
 * di pagamento mostrati/modificati nelle pagine Profilo del partner.
 */
class PartnerProfile extends Model
{
    /** @use HasFactory<PartnerProfileFactory> */
    use HasFactory;

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
        'stripe_account_id',
        'stripe_charges_enabled',
        'stripe_payouts_enabled',
        'stripe_requirements_due',
        'commission_rate_bp',
        'commission_min_cents',
    ];

    protected function casts(): array
    {
        return [
            'stripe_charges_enabled' => 'boolean',
            'stripe_payouts_enabled' => 'boolean',
            'stripe_requirements_due' => 'array',
            'commission_rate_bp' => 'integer',
            'commission_min_cents' => 'integer',
        ];
    }

    /** Può incassare: l'onboarding Stripe è arrivato a charges_enabled. */
    public function canSell(): bool
    {
        return $this->stripe_account_id !== null && $this->stripe_charges_enabled;
    }

    /** Può ricevere bonifici: serve anche payouts_enabled (IBAN verificato su Stripe). */
    public function canBePaid(): bool
    {
        return $this->canSell() && $this->stripe_payouts_enabled;
    }

    /** Aliquota del partner, o quella di piattaforma se non deroga. */
    public function commissionRateBp(): int
    {
        return $this->commission_rate_bp ?? (int) config('commerce.commission.rate_bp');
    }

    /** Soglia del partner, o quella di piattaforma se non deroga (0 è una deroga vera). */
    public function commissionMinCents(): int
    {
        return $this->commission_min_cents ?? (int) config('commerce.commission.min_cents');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
