<?php

namespace App\Livewire\Profile;

use Livewire\Component;

class ProfilePayment extends Component
{
    /** Dati carta mock come da XD "Profilo – metodo pagamento" (scadenza e cvv vuoti: placeholder). */
    public string $cardHolder = 'Giulia Rossi';

    public string $cardNumber = '2876 **** **** ****';

    public string $cardExpiry = '';

    public string $cardCvv = '';

    public function render()
    {
        return view('livewire.profile.profile-payment')->title('Metodo di pagamento — AnimalAmo');
    }
}
