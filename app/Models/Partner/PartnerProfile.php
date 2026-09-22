<?php

namespace App\Models\Partner;

use App\Enums\OrderPaymentMode;
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
        'online_payment',
        'payment_url',
    ];

    protected function casts(): array
    {
        return [
            'online_payment' => 'boolean',
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

    /**
     * Il cliente paga online su AnimalAmo. `!== false` e non `=== true`: un
     * profilo appena creato non ha la colonna in memoria (la scrive il default
     * del database), e va letto come i partner di prima, cioè online.
     */
    public function requiresOnlinePayment(): bool
    {
        return $this->online_payment !== false;
    }

    /**
     * Può mandare servizi a catalogo. Chi si fa pagare direttamente non ha
     * bisogno di Stripe. canSell/canBePaid restano verifiche Stripe pure,
     * perché decidono anche i bonifici degli ordini online già fatti.
     */
    public function canPublish(): bool
    {
        return ! $this->requiresOnlinePayment() || $this->canBePaid();
    }

    public function paymentMode(): OrderPaymentMode
    {
        return $this->requiresOnlinePayment() ? OrderPaymentMode::Online : OrderPaymentMode::OnSite;
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
