<?php

namespace App\Livewire;

use Livewire\Component;

class Profile extends Component
{
    /** Dati personali mock precompilati come da XD "Profilo" (nessun backend). */
    public string $firstName = 'Giulia';

    public string $lastName = 'Rossi';

    public string $birthDate = '22/03/1998';

    /** Copy fedele XD (iniziale maiuscola). */
    public string $email = 'Giulia.rossi@gmail.com';

    public string $petType = 'Cane';

    public string $address = 'Viale Abruzzi 20';

    public string $city = 'Milano';

    public string $zip = '20131';

    public string $phone = '340 5738920';

    /** Campi in ordine XD per colonna (label → proprietà). */
    public const FIELDS_LEFT = [
        'firstName' => 'Nome',
        'lastName' => 'Cognome',
        'birthDate' => 'Data di nascita',
        'email' => 'Email',
        'petType' => 'Tipologia animale',
    ];

    public const FIELDS_RIGHT = [
        'address' => 'Indirizzo',
        'city' => 'Città',
        'zip' => 'Cap',
        'phone' => 'Cellulare',
    ];

    public function render()
    {
        return view('livewire.profile', [
            'fieldsLeft' => self::FIELDS_LEFT,
            'fieldsRight' => self::FIELDS_RIGHT,
        ])->title('Profilo — AnimalAmo');
    }
}
