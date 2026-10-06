<?php

namespace App\Livewire\Forms;

use App\Models\Newsletter\NewsletterSubscriber;
use App\Models\User;
use App\Services\Newsletter\SubscriptionService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Livewire\Form;
use Throwable;

/**
 * Registrazione rapida del cliente (richiesta della cliente, 06/10/2026):
 * nome, email e password, poi si entra subito nel profilo. Cognome, data di
 * nascita, telefono, indirizzo e animale si completano dal profilo quando si
 * vuole; prima erano quattro step obbligatori.
 */
class RegisterForm extends Form
{
    public string $firstName = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    /** Termini e informativa privacy: obbligatoria, la prova è terms_accepted_at. */
    public bool $termsAccepted = false;

    public bool $newsletter = false;

    /** Consenso MARKETING ("...per ricevere promozioni esclusive"): facoltativo per GDPR. */
    public bool $privacyConsent = false;

    protected function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'passwordConfirmation' => ['required', 'same:password'],
            'termsAccepted' => ['accepted'],
            'newsletter' => ['boolean'],
            'privacyConsent' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            // Password ancora da scegliere: "una", non "la" del generico lang (login).
            'password.required' => __('auth.choose_password'),
            'termsAccepted.accepted' => __('auth-modal.register.terms_required'),
        ];
    }

    /** Crea l'utente e assegna il ruolo client; da chiamare dopo la validazione. */
    public function register(): User
    {
        $user = DB::transaction(function (): User {
            $user = User::create([
                'first_name' => $this->firstName,
                'email' => $this->email,
                'password' => $this->password,
                // Vero solo a iscrizione confermata dal link della mail:
                // lo allinea SubscriptionService.
                'newsletter' => false,
                'marketing_consent' => $this->privacyConsent,
                'terms_accepted_at' => now(),
            ]);

            $user->assignRole('client');

            return $user;
        });

        // Casella spuntata = richiesta di iscrizione con double opt-in, e la
        // prova è la frase della casella nella lingua in cui l'ha letta. Un
        // errore qui non deve far fallire una registrazione già salvata.
        if ($this->newsletter) {
            try {
                app(SubscriptionService::class)->subscribe(
                    email: $user->email,
                    locale: app()->getLocale(),
                    source: NewsletterSubscriber::SOURCE_REGISTRATION,
                    consentText: __('auth-modal.register.newsletter'),
                    ip: request()->ip(),
                    userAgent: request()->userAgent(),
                    user: $user,
                );
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        event(new Registered($user));

        return $user;
    }
}
