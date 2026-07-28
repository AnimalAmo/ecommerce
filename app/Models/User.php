<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Favorite\Favorite;
use App\Models\Partner\PartnerApplication;
use App\Models\Partner\PartnerProfile;
use App\Models\Pet\Pet;
use App\Support\Phone;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'first_name',
    'last_name',
    'birth_date',
    'email',
    'password',
    'phone',
    'address',
    'city',
    'postal_code',
    'newsletter',
    'marketing_consent',
    'is_active',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'newsletter' => 'boolean',
            'marketing_consent' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** Il numero arriva da form, seeder e factory: la normalizzazione E.164 vive qui, non nei form. */
    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => Phone::toE164($value));
    }

    /** Nome completo: compatibilità con i punti che usavano la colonna 'name'. */
    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->first_name.' '.$this->last_name));
    }

    /**
     * Carta salvata su Stripe (Profilo → Dati pagamento). Le colonne card_*
     * sono solo lo specchio mascherato del payment method: non sono fillable,
     * le scrive SOLO SavedPaymentMethodService dopo il SetupIntent.
     */
    public function hasSavedCard(): bool
    {
        return $this->stripe_payment_method_id !== null && $this->card_last4 !== null;
    }

    /** Scadenza in formato MM/AA come nel mock XD (vuota se manca la carta). */
    protected function cardExpiry(): Attribute
    {
        return Attribute::get(fn (): string => $this->card_exp_month === null || $this->card_exp_year === null
            ? ''
            : sprintf('%02d/%02d', $this->card_exp_month, $this->card_exp_year % 100));
    }

    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /** Profilo B2B (dati fiscali + pagamento) per gli utenti di ruolo partner. */
    public function partnerProfile(): HasOne
    {
        return $this->hasOne(PartnerProfile::class);
    }

    /** Richieste "diventa partner" inviate dall'area ecommerce. */
    public function partnerApplications(): HasMany
    {
        return $this->hasMany(PartnerApplication::class);
    }

    /**
     * La richiesta ancora aperta (inviata ma non ancora diventata account
     * partner): è quella che riapre il form e precompila l'iscrizione B2B.
     */
    public function openPartnerApplication(): ?PartnerApplication
    {
        return $this->partnerApplications()->open()->latest('id')->first();
    }
}
