<?php

namespace App\Livewire\Partner\Profile;

use Livewire\Component;

class PartnerProfileSecurity extends Component
{
    /** Copy segnaposto sezione privacy (in attesa del testo cliente). */
    public const PRIVACY_PLACEHOLDER = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.';

    /** Copy segnaposto sezione eliminazione account (in attesa del testo cliente). */
    public const DELETE_PLACEHOLDER = 'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.';

    public function render()
    {
        return view('livewire.partner.profile.security', [
            'privacyText' => self::PRIVACY_PLACEHOLDER,
            'deleteText' => self::DELETE_PLACEHOLDER,
        ])->title(__('partner.profile.security_title'));
    }
}
