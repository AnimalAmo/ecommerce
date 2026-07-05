<?php

namespace App\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;

class Checkout extends Component
{
    /** Flag ?regalo=1 (come nel carrello): riepilogo con il solo articolo smartbox + dedica/messaggio. */
    #[Url(as: 'regalo', except: false)]
    public bool $gift = false;

    /** Dedica e messaggio del regalo, letti dalla sessione scritta dal carrello (default mock XD). */
    public string $giftDedication = '';

    public string $giftMessage = '';

    /** Step interno del funnel: 1 = I tuoi dati, 2 = Pagamento, 3 = Fatto! (nessun parametro in URL). */
    public int $step = 1;

    /** Dati personali mock precompilati come da XD (nessun backend). */
    public string $firstName = 'Giulia';

    public string $lastName = 'Rossi';

    public string $email = 'giulia.rossi@gmail.com';

    public string $country = 'Italia';

    public string $phone = '340 5738920';

    /** Metodo di pagamento selezionato ('carta' mostra il sub-form carta). */
    public string $paymentMethod = 'carta';

    /** Dati carta mock (numero e cvv mascherati come in XD). */
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
        if ($this->gift) {
            // TODO: persistenza regalo backend — dedica/messaggio arrivano dalla sessione scritta dal carrello.
            $giftData = session('giftCheckout', []);

            $this->giftDedication = $giftData['dedication'] ?? Cart::GIFT_DEDICATION_DEFAULT;
            $this->giftMessage = $giftData['message'] ?? Cart::GIFT_MESSAGE_DEFAULT;
        }
    }

    /** Avanza di un solo step via CTA (Prosegui → 2, Paga ora → 3); niente salti in avanti né ritorni. */
    public function goToStep(int $step): void
    {
        // TODO: validazione reale + integrazione pagamento — mock: si avanza solo in sequenza.
        if ($step === $this->step + 1 && $step <= 3) {
            $this->step = $step;
        }
    }

    /** Seleziona il metodo di pagamento (mostra/nasconde il sub-form carta). */
    public function selectPayment(string $method): void
    {
        if (in_array($method, self::PAYMENT_METHODS, true)) {
            $this->paymentMethod = $method;
        }
    }

    /** Etichetta ospiti: come in Cart ("N adulti" se solo adulti, altrimenti "N ospiti"). */
    public function guestsLabel(array $guests): string
    {
        $extra = $guests['ragazzi'] + $guests['bambini'];

        if ($extra === 0) {
            return $guests['adulti'] === 1 ? '1 adulto' : $guests['adulti'].' adulti';
        }

        $total = $guests['adulti'] + $extra;

        return $total === 1 ? '1 ospite' : $total.' ospiti';
    }

    /** Etichetta animali: come in Cart ("1 cane" / "N cani"). */
    public function dogsLabel(int $dogs): string
    {
        return $dogs === 1 ? '1 cane' : $dogs.' cani';
    }

    public function render()
    {
        // Riepilogo ordine: stessi articoli del carrello (476 €); in modalità regalo il solo
        // articolo smartbox. NOTA: il mock XD regalo ripete "476 €" per errore di copia del
        // designer — il totale è la somma reale dei prezzi (regalo: 143 €).
        $items = $this->gift ? Cart::GIFT_ITEMS : Cart::ITEMS;

        return view('livewire.checkout', [
            'items' => $items,
            'total' => array_sum(array_column($items, 'price')),
            'steps' => self::STEPS,
            'altMethods' => self::ALT_METHODS,
        ])->title('Checkout — AnimalAmo');
    }
}
