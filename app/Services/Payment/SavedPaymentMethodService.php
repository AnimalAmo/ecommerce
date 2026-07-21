<?php

namespace App\Services\Payment;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * Carta salvata dell'utente (Profilo → Dati pagamento). Il PAN non tocca MAI
 * il nostro server: il browser lo manda a Stripe con un SetupIntent e qui
 * resta solo il payment method id + i dati mascherati da mostrare.
 *
 * Il SetupIntent confermato dal client viene SEMPRE riverificato server-side
 * (stato + appartenenza al customer dell'utente), come il capture del checkout.
 */
class SavedPaymentMethodService
{
    public function __construct(private readonly StripeClient $client) {}

    /**
     * Apre un SetupIntent off_session per l'utente (customer creato al volo).
     *
     * @return array{client_secret: string, setup_intent_id: string}
     *
     * @throws ApiErrorException
     */
    public function createSetupIntent(User $user): array
    {
        $intent = $this->client->setupIntents->create([
            'customer' => $this->customerId($user),
            'payment_method_types' => ['card'],
            // La carta servirà anche a checkout futuri, non solo alla sessione corrente.
            'usage' => 'off_session',
        ]);

        return [
            'client_secret' => (string) $intent->client_secret,
            'setup_intent_id' => (string) $intent->id,
        ];
    }

    /**
     * Riverifica il SetupIntent confermato dal browser e salva la carta.
     * False = nulla è stato salvato (intent non riuscito, di un altro
     * customer, o errore API): il chiamante mostra l'errore.
     */
    public function saveFromSetupIntent(User $user, string $setupIntentId): bool
    {
        $customerId = $user->stripe_customer_id;

        if ($customerId === null || $setupIntentId === '') {
            return false;
        }

        try {
            $intent = $this->client->setupIntents->retrieve($setupIntentId);

            // Il client può inventare qualsiasi id: l'intent deve essere
            // riuscito E appartenere al customer di QUESTO utente.
            if ($intent->status !== 'succeeded' || (string) ($intent->customer ?? '') !== $customerId) {
                Log::warning('Stripe setup: SetupIntent non valido per l\'utente', [
                    'user_id' => $user->getKey(),
                    'setup_intent_id' => $setupIntentId,
                    'status' => $intent->status,
                ]);

                return false;
            }

            $paymentMethodId = (string) ($intent->payment_method ?? '');

            if ($paymentMethodId === '') {
                return false;
            }

            $paymentMethod = $this->client->paymentMethods->retrieve($paymentMethodId);

            // Default del customer: la carta usata dai futuri addebiti off_session.
            $this->client->customers->update($customerId, [
                'invoice_settings' => ['default_payment_method' => $paymentMethodId],
            ]);

            $previous = $user->stripe_payment_method_id;
        } catch (ApiErrorException $exception) {
            Log::warning('Stripe setup: salvataggio del metodo di pagamento fallito', [
                'user_id' => $user->getKey(),
                'setup_intent_id' => $setupIntentId,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        // StripeObject::__get urla sulle proprietà assenti: sempre via isset.
        $card = isset($paymentMethod->card) ? $paymentMethod->card : null;
        $holder = isset($paymentMethod->billing_details->name) ? $paymentMethod->billing_details->name : null;

        $user->forceFill([
            'stripe_payment_method_id' => $paymentMethodId,
            'card_brand' => $card?->brand,
            'card_last4' => $card?->last4,
            'card_exp_month' => $card?->exp_month,
            'card_exp_year' => $card?->exp_year,
            'card_holder' => $holder,
        ])->save();

        // La vecchia carta resterebbe agganciata al customer: staccata solo
        // ORA, a nuova carta già persistita (se fallisce non perdiamo nulla).
        if ($previous !== null && $previous !== $paymentMethodId) {
            $this->detach($previous, $user);
        }

        return true;
    }

    /** Rimuove la carta salvata: detach su Stripe + colonne ripulite. */
    public function forget(User $user): void
    {
        $paymentMethodId = $user->stripe_payment_method_id;

        if ($paymentMethodId !== null) {
            $this->detach($paymentMethodId, $user);
        }

        $user->forceFill([
            'stripe_payment_method_id' => null,
            'card_brand' => null,
            'card_last4' => null,
            'card_exp_month' => null,
            'card_exp_year' => null,
            'card_holder' => null,
        ])->save();
    }

    /**
     * Customer Stripe dell'utente, creato (o ricreato) se serve.
     *
     * @throws ApiErrorException
     */
    private function customerId(User $user): string
    {
        $customerId = $user->stripe_customer_id;

        if ($customerId !== null && $this->customerExists($customerId)) {
            return $customerId;
        }

        $customer = $this->client->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => ['user_id' => (string) $user->getKey()],
        ]);

        // Customer nuovo: l'eventuale pm del customer sparito non esiste più.
        $user->forceFill([
            'stripe_customer_id' => $customer->id,
            'stripe_payment_method_id' => null,
            'card_brand' => null,
            'card_last4' => null,
            'card_exp_month' => null,
            'card_exp_year' => null,
            'card_holder' => null,
        ])->save();

        return (string) $customer->id;
    }

    /** Customer cancellato dalla dashboard: va ricreato, non riusato. */
    private function customerExists(string $customerId): bool
    {
        try {
            $customer = $this->client->customers->retrieve($customerId);
        } catch (ApiErrorException) {
            return false;
        }

        return ! (isset($customer->deleted) && $customer->deleted);
    }

    /** Detach best-effort: un pm già staccato non deve rompere il flusso. */
    private function detach(string $paymentMethodId, User $user): void
    {
        try {
            $this->client->paymentMethods->detach($paymentMethodId);
        } catch (ApiErrorException $exception) {
            Log::warning('Stripe setup: detach del metodo di pagamento fallito', [
                'user_id' => $user->getKey(),
                'payment_method_id' => $paymentMethodId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
