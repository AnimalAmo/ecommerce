<?php

namespace App\Livewire\Profile;

use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Profile extends Component
{
    /** Dati personali dell'utente autenticato (caricati in mount). */
    public string $firstName = '';

    public string $lastName = '';

    /** Formato italiano gg/mm/aaaa come da XD. */
    public string $birthDate = '';

    public string $email = '';

    /** Specie del primo animale dell'utente (il design ne prevede uno solo). */
    public string $petType = '';

    public string $address = '';

    public string $city = '';

    public string $zip = '';

    public string $phone = '';

    /** Password attuale: richiesta solo quando si cambia l'email (anti-takeover). */
    public string $currentPassword = '';

    /** Campi in ordine XD per colonna (proprietà → chiave lang della label). */
    public const FIELDS_LEFT = [
        'firstName' => 'profile.field_first_name',
        'lastName' => 'profile.field_last_name',
        'birthDate' => 'profile.field_birth_date',
        'email' => 'profile.field_email',
        'petType' => 'profile.field_pet_type',
    ];

    public const FIELDS_RIGHT = [
        'address' => 'profile.field_address',
        'city' => 'profile.field_city',
        'zip' => 'profile.field_zip',
        'phone' => 'profile.field_phone',
    ];

    public function mount(): void
    {
        $user = Auth::user();

        $this->firstName = $user->first_name;
        $this->lastName = $user->last_name;
        $this->birthDate = $user->birth_date?->format('d/m/Y') ?? '';
        $this->email = $user->email;
        $this->petType = $user->pets()->first()?->species ?? '';
        $this->address = $user->address ?? '';
        $this->city = $user->city ?? '';
        $this->zip = $user->postal_code ?? '';
        $this->phone = $user->phone ?? '';
    }

    protected function rules(): array
    {
        $rules = [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'birthDate' => ['required', 'date_format:d/m/Y', 'before:today'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(Auth::id())],
            'petType' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:10'],
            'phone' => ['required', 'string', 'max:30'],
        ];

        // Cambiare l'email consente il takeover permanente (recupero password
        // dirottato): esige la password attuale come ri-autenticazione.
        if ($this->email !== Auth::user()->email) {
            $rules['currentPassword'] = ['required', 'current_password'];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            // "In uso", non "registrata" del custom lang: qui l'email appartiene a un altro account.
            'email.unique' => __('profile.email_in_use'),
            'currentPassword.required' => __('profile.current_password_for_email'),
            'currentPassword.current_password' => __('profile.current_password_incorrect'),
        ];
    }

    public function save(): void
    {
        $this->validate();

        $user = Auth::user();

        $user->update([
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'birth_date' => Carbon::createFromFormat('d/m/Y', $this->birthDate)->startOfDay(),
            'email' => $this->email,
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->zip,
            'phone' => $this->phone,
        ]);

        // Aggiorna la specie del primo animale, o lo crea se assente.
        $user->pets()->updateOrCreate([], ['species' => $this->petType]);

        $this->reset('currentPassword');

        Flux::toast(text: __('profile.saved'), variant: 'success');
    }

    public function render()
    {
        return view('livewire.profile.profile', [
            'fieldsLeft' => array_map(fn ($key) => __($key), self::FIELDS_LEFT),
            'fieldsRight' => array_map(fn ($key) => __($key), self::FIELDS_RIGHT),
        ])->title(__('profile.title_profile'));
    }
}
