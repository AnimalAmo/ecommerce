<?php

namespace App\Livewire;

use Livewire\Component;

class ProfileSecurity extends Component
{
    /** Password mock mascherate come da XD "Profilo – sicurezza" (15 asterischi). */
    public string $password = '***************';

    public string $passwordConfirm = '***************';

    /** Testo segnaposto delle sezioni privacy come da mock XD. */
    public const PRIVACY_PLACEHOLDER = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.';

    public function render()
    {
        return view('livewire.profile-security', [
            'privacyPlaceholder' => self::PRIVACY_PLACEHOLDER,
        ])->title('Sicurezza — AnimalAmo');
    }
}
