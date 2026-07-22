<?php

namespace App\Livewire\Partner\Profile;

use Livewire\Component;

class PartnerProfileSecurity extends Component
{
    /** Copy sezione privacy vuota: in attesa del testo della cliente (paragrafo guardato nel blade). */
    public const PRIVACY_PLACEHOLDER = '';

    /** Copy sezione eliminazione account vuota: in attesa del testo della cliente (flusso backend pendente). */
    public const DELETE_PLACEHOLDER = '';

    public function render()
    {
        return view('livewire.partner.profile.security', [
            'privacyText' => self::PRIVACY_PLACEHOLDER,
            'deleteText' => self::DELETE_PLACEHOLDER,
        ])->title(__('partner.profile.security_title'));
    }
}
