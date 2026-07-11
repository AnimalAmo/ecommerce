<?php

namespace App\Services\Partner;

use App\Mail\PartnerInvitationMail;
use App\Models\Partner\PartnerApplication;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Invia al candidato l'email con il link (firmato: abilita il prefill dello
 * step 1) all'iscrizione B2B e marca la candidatura come invitata.
 *
 * MVP: chiamato subito al submit di "Lavora con noi". Quando arriverà la
 * moderazione superadmin, questo service resterà l'azione di accettazione.
 */
class SendPartnerInvitation
{
    public function send(PartnerApplication $application): void
    {
        $link = URL::signedRoute('partner.register', ['application' => $application->id]);

        Mail::to($application->email)->send(new PartnerInvitationMail($application, $link));

        $application->update([
            'status' => PartnerApplication::STATUS_INVITED,
            'invited_at' => now(),
        ]);
    }
}
