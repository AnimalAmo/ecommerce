<?php

namespace App\Livewire\Forms;

use App\Models\User;
use App\Support\Phone;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Form;

class RegisterForm extends Form
{
    // Step 1 — informazioni personali
    public string $firstName = '';

    public string $lastName = '';

    public string $birthDate = '';

    public string $email = '';

    // Step 2 — credenziali e contatti
    public string $phone = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    // Step 3 — indirizzo
    public string $address = '';

    public string $city = '';

    public string $postalCode = '';

    // Step 4 — animale domestico e consensi
    public string $petType = '';

    public bool $newsletter = false;

    public bool $privacyConsent = false;

    /** Regole del singolo step del wizard. */
    public function stepRules(int $step): array
    {
        return match ($step) {
            1 => [
                'firstName' => ['required', 'string', 'max:255'],
                'lastName' => ['required', 'string', 'max:255'],
                'birthDate' => ['required', 'date', 'before:today'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            ],
            2 => [
                'phone' => ['required', ...Phone::rules()],
                'password' => ['required', 'string', 'min:8'],
                'passwordConfirmation' => ['required', 'same:password'],
            ],
            3 => [
                'address' => ['required', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:255'],
                'postalCode' => ['required', 'string', 'max:10'],
            ],
            default => [
                'petType' => ['required', 'string', 'max:100'],
                'newsletter' => ['boolean'],
                // Consenso MARKETING ("...per ricevere promozioni esclusive"): facoltativo
                // per GDPR. Il design non ha una checkbox privacy/ToS obbligatoria — da
                // segnalare al cliente (vedi docs/analisi-entita-dinamiche.md).
                'privacyConsent' => ['boolean'],
            ],
        };
    }

    /** Tutte le regole insieme: riconvalida finale prima della creazione. */
    protected function rules(): array
    {
        return array_merge(...array_map($this->stepRules(...), [1, 2, 3, 4]));
    }

    protected function messages(): array
    {
        return [
            // Password ancora da scegliere: "una", non "la" del generico lang (login).
            'password.required' => __('auth.choose_password'),
        ];
    }

    /** Convalida i soli campi dello step indicato. */
    public function validateStep(int $step): void
    {
        $this->validate($this->stepRules($step));
    }

    /** Primo step con errori (chiavi già prefissate 'form.'), per riposizionare il wizard. */
    public function firstInvalidStep(array $errorKeys): int
    {
        $fields = array_map(fn (string $key): string => Str::after($key, 'form.'), $errorKeys);

        foreach ([1, 2, 3] as $step) {
            if (array_intersect(array_keys($this->stepRules($step)), $fields) !== []) {
                return $step;
            }
        }

        return 4;
    }

    /** Crea utente + animale e assegna il ruolo client; da chiamare dopo la validazione. */
    public function register(): User
    {
        $user = DB::transaction(function (): User {
            $user = User::create([
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'birth_date' => $this->birthDate,
                'email' => $this->email,
                'password' => $this->password,
                'phone' => $this->phone,
                'address' => $this->address,
                'city' => $this->city,
                'postal_code' => $this->postalCode,
                'newsletter' => $this->newsletter,
                'marketing_consent' => $this->privacyConsent,
            ]);

            $user->pets()->create(['species' => $this->petType]);

            $user->assignRole('client');

            return $user;
        });

        event(new Registered($user));

        return $user;
    }
}
