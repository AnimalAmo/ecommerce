<?php

namespace App\Livewire;

use App\Data\Cart\CartItemData;
use App\Services\Cart\CartManager;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

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

    /** Paese statico: nessuna colonna a db (fatturazione = step 4). */
    public string $country = 'Italia';

    public string $phone = '';

    /**
     * Email del destinatario della smartbox regalo (solo flusso regalo): campo
     * assente nell'XD ma necessario per l'invio reale — raccolto allo step 1 e
     * persistito nelle options.gift di tutte le righe regalo al goToStep(2).
     */
    public string $recipientEmail = '';

    /** Metodo di pagamento selezionato ('carta' mostra il sub-form carta). */
    public string $paymentMethod = 'carta';

    /** Dati carta mock come da XD (numero e cvv mascherati; pagamento reale = step 4). */
    public string $cardHolder = 'Giulia Rossi';

    public string $cardNumber = '2876 **** **** ****';

    public string $cardExpiry = '10/29';

    public string $cardCvv = '***';

    /** Tab dello stepper (statici: si avanza solo con le CTA, i tab non sono cliccabili). */
    public const STEPS = [1 => 'I tuoi dati', 2 => 'Pagamento', 3 => 'Fatto!'];

    /** Metodi di pagamento ammessi. */
    public const PAYMENT_METHODS = ['carta', 'apple', 'google', 'klarna', 'paypal'];

    /** Metodi alternativi alla carta, in ordine XD (copy fedele: "Google Play"). */
    public const ALT_METHODS = [
        'apple' => 'Apple Pay',
        'google' => 'Google Play',
        'klarna' => 'Klarna',
        'paypal' => 'Paypal',
    ];

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
     * Avanza di un solo step via CTA (Prosegui → 2, Paga ora → 3); niente salti
     * in avanti né ritorni. Il passaggio allo step 2 valida i dati personali e,
     * in modalità regalo, persiste l'email del destinatario sulle righe.
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
        }

        // TODO: integrazione pagamento reale (step 4) — "Paga ora" resta mock.
        $this->step = $step;
    }

    /** Seleziona il metodo di pagamento (mostra/nasconde il sub-form carta). */
    public function selectPayment(string $method): void
    {
        if (in_array($method, self::PAYMENT_METHODS, true)) {
            $this->paymentMethod = $method;
        }
    }

    public function render()
    {
        // Riepilogo ordine: le righe reali del carrello (filtrate sul flusso corrente),
        // stesso contratto card del carrello; totale in cents via CartManager.
        $items = $this->cart()->items($this->gift)
            ->map(fn (CartItemData $item): array => $this->presentItem($item))
            ->values()
            ->all();

        return view('livewire.checkout', [
            'items' => $items,
            'total' => $this->cart()->total($this->gift),
            'steps' => self::STEPS,
            'altMethods' => self::ALT_METHODS,
            // Step 3 "Fatto!": email destinataria dalle options della prima riga regalo.
            'giftRecipientEmail' => $this->cart()->items(true)->first()?->options['gift']['recipient_email'] ?? $this->recipientEmail,
        ])->title('Checkout — AnimalAmo');
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
