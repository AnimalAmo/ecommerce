<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Favorite\Favorite;
use App\Models\Partner\PartnerProfile;
use App\Models\Pet\Pet;
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

    /** Nome completo: compatibilità con i punti che usavano la colonna 'name'. */
    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->first_name.' '.$this->last_name));
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
}
