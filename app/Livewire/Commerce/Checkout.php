<?php

namespace App\Livewire\Commerce;

use App\Actions\Order\PlaceOrderAction;
use App\Contracts\Payment\PaymentGatewayInterface;
use App\Data\Cart\CartItemData;
use App\Data\Checkout\PlaceOrderData;
use App\Enums\PaymentMethod;
use App\Exceptions\CartValidationException;
use App\Exceptions\OrderAlreadyPlacedException;
use App\Exceptions\PaymentConfigurationException;
use App\Services\Availability\AvailabilityService;
use App\Services\Cart\CartManager;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Payment\PaymentGatewayService;
use Flux\Flux;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

class Checkout extends Component
{
    /** Flag ?regalo=1 (come nel carrello): checkout con le SOLE righe regalo (flussi separati, mai vista mista). */
    #[Url(as: 'regalo', except: false)]
    public bool $gift = false;

    /** Step interno del funnel: 1 = I tuoi dati, 2 = Pagamento, 3 = Fatto! (nessun parametro in URL). */
    public int $step = 1;

    /** Dati personali: precompilati dall'utente autenticato, vuoti da guest. */
    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    /** Paese statico: nessuna colonna a db (fatturazione = step successivi). */
    public string $country = 'Italia';

    public string $phone = '';

    /**
     * Email del destinatario della smartbox regalo (solo flusso regalo): campo
     * assente nell'XD ma necessario per l'invio reale — raccolto allo step 1 e
     * persistito nelle options.gift di tutte le righe regalo al goToStep(2).
     */
    public string $recipientEmail = '';

    /** Metodo di pagamento selezionato (value di PaymentMethod: 'card', 'apple_pay', ...). */
    public string $paymentMethod = 'card';

    /** Sessione Stripe corrente (Payment/Express Checkout Element). */
    public ?string $clientSecret = null;

    /** PaymentIntent riusato agli switch di metodo Stripe (update dei types, mai un PI orfano a ogni click). */
    public ?string $paymentIntentId = null;

    /**
     * Importo (cents) con cui è stata aperta la sessione gateway corrente: se
     * al "Paga ora" il carrello (cambiato in un'altra tab) non coincide più,
     * la sessione va ri-allineata PRIMA di confermare.
     */
    public ?int $sessionAmountCents = null;

    /** True dal click su "Paga ora" all'esito della conferma JS (bottone disabilitato). */
    public bool $processing = false;

    /** True quando l'Element/SDK del metodo corrente è montato: "Paga ora" resta spento prima. */
    public bool $elementReady = false;

    /** Gateway non configurato/abilitato: box informativo al posto dell'element, nessun crash. */
    public bool $paymentUnavailable = false;

    /**
     * Snapshot delle card riepilogo scattato PRIMA di creare l'ordine: la
     * pipeline svuota il flusso ordinato dal carrello, ma lo step 3 deve
     * continuare a mostrare le righe acquistate.
     */
    public array $placedItems = [];

    /** Email destinataria mostrata allo step 3 del flusso regalo (dallo snapshot, il carrello è ormai vuoto). */
    public string $placedGiftRecipientEmail = '';

    /** Tab dello stepper (statici: si avanza solo con le CTA, i tab non sono cliccabili). */
    public const STEPS = [1 => 'I tuoi dati', 2 => 'Pagamento', 3 => 'Fatto!'];

    public function mount(): void
    {
        // Carrello (filtrato sul flusso corrente) vuoto: niente checkout, si torna al carrello.
        if ($this->cart()->items($this->gift)->isEmpty()) {
            $this->redirectRoute('carrello', $this->gift ? ['regalo' => 1] : []);

            return;
        }

        // Step 1 precompilato dall'utente autenticato (guest: campi vuoti).
        if (($user = Auth::user()) !== null) {
            $this->firstName = $user->first_name;
            $this->lastName = $user->last_name;
            $this->email = $user->email;
            $this->phone = $user->phone ?? '';
        }
    }

    /**
     * Avanza di un solo step via CTA; niente salti in avanti né ritorni. Il
     * passaggio allo step 2 valida i dati personali, in modalità regalo
     * persiste l'email del destinatario sulle righe, pre-verifica la
     * disponibilità di tutte le righe e apre la sessione di pagamento.
     * Lo step 3 reale arriva SOLO da handlePaymentCallback: goToStep(3) resta
     * un'anteprima UI senza effetti (nessun ordine, nessuna email).
     */
    public function goToStep(int $step): void
    {
        if ($step !== $this->step + 1 || $step > 3) {
            return;
        }

        if ($step === 2) {
            $this->validate([
                'firstName' => ['required'],
                'lastName' => ['required'],
                'email' => ['required', 'email'],
                ...($this->gift ? ['recipientEmail' => ['required', 'email']] : []),
            ]);

            if ($this->gift) {
                foreach ($this->cart()->items(true) as $item) {
                    $this->cart()->updateGift($item->key, ['recipient_email' => $this->recipientEmail]);
                }
            }

            // Pre-check di TUTTE le righe (chiusure, date passate, capienza
            // eventi coi posti già venduti): meglio fermarsi qui che dopo
            // l'incasso — il lock definitivo resta in ReserveAvailabilityPipe.
            try {
                $this->ensureCartIsAvailable();
            } catch (CartValidationException $exception) {
                Flux::toast(text: $exception->getMessage(), variant: 'danger');

                return;
            }

            $this->ensureMethodIsAvailable();
            $this->initPaymentSession();
        }

        $this->step = $step;
    }

    /** Seleziona il metodo di pagamento (riga abilitata) e riapre la sessione gateway (update del PaymentIntent). */
    public function selectPayment(string $method): void
    {
        $selected = PaymentMethod::tryFrom($method);

        if ($selected === null || ! $this->isMethodAvailable($selected) || $this->paymentMethod === $method) {
            return;
        }

        $this->paymentMethod = $method;

        if ($this->step === 2) {
            $this->initPaymentSession();
        }
    }

    /** "Paga ora": congela il bottone e delega la conferma al JS (Payment Element del metodo corrente). */
    public function processPayment(): void
    {
        if ($this->step !== 2 || $this->processing || $this->paymentUnavailable) {
            return;
        }

        // Il carrello può essere cambiato in un'altra tab dopo l'init: il
        // PaymentIntent va ri-allineato al totale corrente PRIMA di
        // confermare — niente dispatch, si ricontrolla. PI nuovo, non update:
        // un update manterrebbe lo stesso client_secret e il wire:key non
        // rimonterebbe l'Element — elementReady resterebbe false per sempre.
        if ($this->sessionAmountCents !== null && $this->cart()->total($this->gift) !== $this->sessionAmountCents) {
            $this->paymentIntentId = null;
            $this->initPaymentSession();
            Flux::toast(text: __('payment.errors.total_updated'), variant: 'danger');

            return;
        }

        $this->processing = true;
        $this->dispatch('process-payment', method: $this->paymentMethod);
    }

    /** L'Element/SDK del metodo corrente è montato: il JS abilita "Paga ora". */
    public function markElementReady(): void
    {
        $this->elementReady = true;
    }

    /** Init JS fallito (SDK non caricato, mount rotto): box informativo, mai un "Paga ora" morto. */
    public function reportPaymentInitFailed(): void
    {
        $this->processing = false;
        $this->elementReady = false;
        $this->paymentUnavailable = true;
        Flux::toast(text: __('payment.errors.init_failed'), variant: 'danger');
    }

    /**
     * Esito della conferma client (Payment/Express Checkout Element):
     * riverifica il pagamento server-side (captureFromCheckout) e SOLO a
     * capture valido crea l'ordine in pipeline. Sold-out concorrente
     * post-capture: rollback totale già avvenuto → storno immediato col
     * transaction id del capture e si resta allo step 2.
     */
    public function handlePaymentCallback(array $payload): void
    {
        $method = PaymentMethod::tryFrom($this->paymentMethod);

        // Metodo manomesso o gateway disabilitato nel frattempo: nessuna capture.
        if ($this->step !== 2 || $method === null || ! $this->isMethodAvailable($method)) {
            $this->processing = false;
            Flux::toast(text: __('payment.errors.config_missing'), variant: 'danger');

            return;
        }

        try {
            $gateway = app(PaymentGatewayFactory::class)->make($method);
        } catch (PaymentConfigurationException) {
            $this->processing = false;
            Flux::toast(text: __('payment.errors.config_missing'), variant: 'danger');

            return;
        }

        // Il payload del client non può puntare a un PI diverso dalla
        // sessione corrente; il backstop resta il guard idempotente a db
        // (provider + gateway_session_id unici).
        if (! $this->payloadMatchesSession($payload)) {
            $this->processing = false;
            Flux::toast(text: __('payment.errors.capture_failed'), variant: 'danger');

            return;
        }

        // Importo atteso SEMPRE dal CartManager server-side, mai dal client.
        $totalCents = $this->cart()->total($this->gift);
        $capture = $gateway->captureFromCheckout($payload, $totalCents);

        if (! $capture->succeeded) {
            // Incassato ma NON valido (importo/valuta cambiati fra init e
            // conferma): se l'incasso appartiene a un ordine già registrato è
            // un replay (esito idempotente, NIENTE refund), altrimenti sono
            // soldi orfani da stornare SUBITO.
            if ($capture->fundsCaptured) {
                if (app(PlaceOrderAction::class)->findRegisteredOrder($capture) !== null) {
                    $this->finishAsAlreadyPlaced();

                    return;
                }

                Log::critical('Checkout: capture incassato ma non valido, storno immediato', [
                    'provider' => $capture->provider,
                    'transaction_id' => $capture->transactionId,
                    'captured_amount_cents' => $capture->capturedAmountCents,
                    'expected_amount_cents' => $totalCents,
                ]);
                $this->refundCapture($gateway, $capture->transactionId, $capture->capturedAmountCents ?? $totalCents);

                $this->processing = false;
                $this->paymentIntentId = null;
                $this->initPaymentSession();
                Flux::toast(text: __('payment.errors.amount_changed'), variant: 'danger');

                return;
            }

            $this->processing = false;
            Flux::toast(text: $capture->errorMessage ?? __('payment.errors.capture_failed'), variant: 'danger');

            return;
        }

        $items = $this->cart()->items($this->gift);

        // Snapshot per lo step 3 PRIMA della pipeline (ClearCartPipe svuota il flusso).
        $placedItems = $items->map(fn (CartItemData $item): array => $this->presentItem($item))->values()->all();
        $placedGiftRecipientEmail = $items->first(fn (CartItemData $item): bool => $item->isGift)
            ?->options['gift']['recipient_email'] ?? $this->recipientEmail;

        $data = new PlaceOrderData(
            firstName: $this->firstName,
            lastName: $this->lastName,
            email: $this->email,
            phone: $this->phone !== '' ? $this->phone : null,
            country: $this->country,
            gift: $this->gift,
            paymentMethod: $method,
            capture: $capture,
            items: $items,
            totalCents: $totalCents,
        );

        try {
            app(PlaceOrderAction::class)->execute($data);
        } catch (OrderAlreadyPlacedException) {
            // Esito IDEMPOTENTE (replay callback/return URL): l'incasso
            // appartiene all'ordine già registrato — NIENTE refund, NIENTE
            // secondo ordine. Intercettata PRIMA del catch-Throwable apposta.
            $this->finishAsAlreadyPlaced();

            return;
        } catch (CartValidationException) {
            // Post-capture non esiste alcun OrderPayment (rollback totale):
            // lo storno usa il transaction id del capture result.
            $this->refundCapture($gateway, $capture->transactionId, $totalCents);

            // Il PaymentIntent incassato/stornato non è riusabile: sessione nuova per riprovare.
            $this->processing = false;
            $this->paymentIntentId = null;
            $this->initPaymentSession();
            Flux::toast(text: __('payment.errors.refunded_after_soldout'), variant: 'danger');

            return;
        } catch (Throwable $exception) {
            // QUALSIASI altro errore post-capture = soldi presi senza ordine:
            // storno immediato, mai un incasso orfano.
            Log::critical('Checkout: pipeline ordine fallita post-capture, storno immediato', [
                'provider' => $capture->provider,
                'transaction_id' => $capture->transactionId,
                'amount_cents' => $totalCents,
                'error' => $exception->getMessage(),
            ]);
            $this->refundCapture($gateway, $capture->transactionId, $totalCents);

            $this->processing = false;
            $this->paymentIntentId = null;
            $this->initPaymentSession();
            Flux::toast(text: __('payment.errors.refunded_after_error'), variant: 'danger');

            return;
        }

        $this->placedItems = $placedItems;
        $this->placedGiftRecipientEmail = (string) $placedGiftRecipientEmail;
        $this->processing = false;
        $this->dispatch('cart-updated');
        $this->step = 3;
    }

    /** Conferma JS fallita (carta rifiutata, wallet chiuso, errore SDK): si resta allo step 2. */
    public function onPaymentFailed(string $message): void
    {
        $this->processing = false;
        Flux::toast(text: $message !== '' ? $message : __('payment.errors.capture_failed'), variant: 'danger');
    }

    public function render()
    {
        // Riepilogo ordine: le righe reali del carrello (filtrate sul flusso corrente),
        // stesso contratto card del carrello; allo step 3 lo snapshot pre-pipeline.
        $items = $this->step === 3 && $this->placedItems !== []
            ? $this->placedItems
            : $this->cart()->items($this->gift)
                ->map(fn (CartItemData $item): array => $this->presentItem($item))
                ->values()
                ->all();

        $methods = $this->availableMethods();

        return view('livewire.commerce.checkout', [
            'items' => $items,
            'total' => $this->cart()->total($this->gift),
            // Etichette dei tab dello stepper localizzate (le chiavi numeriche restano da STEPS).
            'steps' => [
                1 => __('checkout.ui.step_data'),
                2 => __('checkout.ui.step_payment'),
                3 => __('checkout.ui.step_done'),
            ],
            // Righe metodo: solo i PaymentMethod il cui gateway è abilitato.
            'hasCardMethod' => in_array(PaymentMethod::Card, $methods, true),
            'altMethods' => array_values(array_filter($methods, fn (PaymentMethod $method): bool => $method !== PaymentMethod::Card)),
            // Chiave pubblica per il JS dalla config (mai VITE_*).
            'stripeKey' => (string) config('payment.stripe.key'),
            'returnUrl' => $this->returnUrl(),
            // Step 3 "Fatto!": email destinataria dallo snapshot dell'ordine, in
            // anteprima UI dalle options della prima riga regalo ancora in carrello.
            'giftRecipientEmail' => $this->placedGiftRecipientEmail !== ''
                ? $this->placedGiftRecipientEmail
                : ($this->cart()->items(true)->first()?->options['gift']['recipient_email'] ?? $this->recipientEmail),
        ])->title(__('checkout.ui.page_title'));
    }

    /**
     * Apre (o aggiorna) la sessione di pagamento del metodo corrente. Config
     * mancante (chiavi .env vuote) o errore API: toast + box informativo al
     * posto dell'element — il checkout non crasha MAI per un gateway rotto.
     */
    private function initPaymentSession(): void
    {
        $this->clientSecret = null;
        $this->sessionAmountCents = null;
        $this->elementReady = false;
        $this->paymentUnavailable = false;

        $method = PaymentMethod::tryFrom($this->paymentMethod);

        if ($method === null || ! $this->isMethodAvailable($method)) {
            $this->paymentUnavailable = true;

            return;
        }

        $amountCents = $this->cart()->total($this->gift);

        try {
            $gateway = app(PaymentGatewayFactory::class)->make($method);

            // Stesso PI aggiornato agli switch card/apple/google.
            $session = $gateway->initPaymentSession(
                $amountCents,
                $method,
                $this->paymentIntentId !== null ? ['payment_intent_id' => $this->paymentIntentId] : [],
            );

            $this->clientSecret = $session['client_secret'];
            $this->paymentIntentId = $session['payment_intent_id'];

            // Importo della sessione appena aperta: confrontato al "Paga ora"
            // col totale corrente per intercettare i carrelli cambiati altrove.
            $this->sessionAmountCents = $amountCents;
        } catch (PaymentConfigurationException) {
            $this->paymentUnavailable = true;
            Flux::toast(text: __('payment.errors.config_missing'), variant: 'danger');
        } catch (Throwable $exception) {
            Log::warning('Checkout: init della sessione di pagamento fallita', [
                'method' => $method->value,
                'error' => $exception->getMessage(),
            ]);

            $this->paymentUnavailable = true;
            Flux::toast(text: __('payment.errors.init_failed'), variant: 'danger');
        }
    }

    /**
     * Il payload del client deve puntare al PaymentIntent della sessione
     * corrente: un id diverso è un tentativo di replay via devtools.
     * Proprietà nulla (init mai riuscito): il controllo passa al guard
     * idempotente a db.
     */
    private function payloadMatchesSession(array $payload): bool
    {
        return $this->paymentIntentId === null
            || (string) ($payload['payment_intent_id'] ?? '') === $this->paymentIntentId;
    }

    /**
     * Esito idempotente del replay (fix A): l'incasso appartiene all'ordine
     * già registrato — nessun refund, nessun secondo ordine, toast neutro e
     * si va agli acquisti (lo snapshot step 3 del primo ordine non è più
     * ricostruibile da un componente nuovo). Copre anche la race del carrello
     * guest condiviso fra sessioni: il vincolo unico provider+gateway_session_id
     * è il backstop a db, qui serve solo un'uscita cortese.
     */
    private function finishAsAlreadyPlaced(): void
    {
        $this->processing = false;
        Flux::toast(text: __('payment.errors.already_placed'));
        $this->redirectRoute(Auth::check() ? 'profilo.ordini' : 'home');
    }

    /** Storno best-effort del capture orfano: se fallisce si logga FORTE (storno manuale da dashboard). */
    private function refundCapture(PaymentGatewayInterface $gateway, ?string $transactionId, int $amountCents): void
    {
        try {
            $gateway->refund((string) $transactionId, $amountCents);
        } catch (Throwable $exception) {
            Log::critical('Checkout: refund post sold-out FALLITO, stornare manualmente', [
                'transaction_id' => $transactionId,
                'amount_cents' => $amountCents,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /** Pre-check read-only di tutte le righe del flusso (stessa AvailabilityService dei widget). */
    private function ensureCartIsAvailable(): void
    {
        $availability = app(AvailabilityService::class);

        foreach ($this->cart()->items($this->gift) as $item) {
            $purchasable = Relation::getMorphedModel($item->type)::find($item->purchasableId);

            if ($purchasable === null) {
                throw CartValidationException::notPurchasable();
            }

            $availability->ensureAvailable($purchasable, $item->options);
        }
    }

    /** Metodo corrente spento (gateway disabilitato/manomesso): fallback sulla prima riga abilitata. */
    private function ensureMethodIsAvailable(): void
    {
        $methods = $this->availableMethods();

        if ($methods === []) {
            $this->paymentUnavailable = true;

            return;
        }

        $current = PaymentMethod::tryFrom($this->paymentMethod);

        if ($current === null || ! in_array($current, $methods, true)) {
            $this->paymentMethod = $methods[0]->value;
        }
    }

    /**
     * Metodi mostrabili: PaymentMethod il cui gateway è abilitato in
     * payment_gateways (righe dei gateway spenti nascoste).
     *
     * @return list<PaymentMethod>
     */
    private function availableMethods(): array
    {
        $enabledCodes = app(PaymentGatewayService::class)->enabledCodes();

        return array_values(array_filter(
            PaymentMethod::cases(),
            fn (PaymentMethod $method): bool => in_array($method->gatewayCode(), $enabledCodes, true),
        ));
    }

    private function isMethodAvailable(PaymentMethod $method): bool
    {
        return in_array($method, $this->availableMethods(), true);
    }

    /** return_url richiesto da confirmPayment (i metodi attivi non reindirizzano mai). */
    private function returnUrl(): string
    {
        return route('checkout', $this->gift ? ['regalo' => 1] : []);
    }

    /** Facciata carrello (singleton: storage sessione da guest, db da autenticato). */
    private function cart(): CartManager
    {
        return app(CartManager::class);
    }

    /**
     * DTO riga → array della card blade (stesso contratto del carrello): id =
     * chiave riga, prezzo in cents (display via Format::money), animali
     * {specie: count} (label via Format::animals), chip = ProductType REALE,
     * più i metadati regalo (dedica/messaggio mostrati solo se valorizzati).
     */
    private function presentItem(CartItemData $item): array
    {
        return [
            'id' => $item->key,
            'type' => $item->productType,
            'title' => $item->title,
            'location' => $item->location,
            'photoUrl' => $item->photoUrl,
            'dates' => $item->dates,
            'serviceSlot' => $item->serviceSlot,
            // Evento: niente ospiti nelle options, la riga mostra i partecipanti (sempre 1 dalla pagina).
            'guests' => match (true) {
                isset($item->options['guests']) => $item->options['guests'],
                isset($item->options['participants']) => ['adulti' => (int) $item->options['participants'], 'ragazzi' => 0, 'bambini' => 0],
                default => null,
            },
            'animals' => $item->options['animals'] ?? null,
            'price' => $item->priceCents,
            'gift' => $item->isGift,
            'giftDedication' => $item->options['gift']['dedication'] ?? null,
            'giftMessage' => $item->options['gift']['message'] ?? null,
        ];
    }
}
