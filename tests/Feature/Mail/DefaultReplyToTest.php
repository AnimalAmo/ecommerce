<?php

namespace Tests\Feature\Mail;

use App\Mail\ContactMessageMail;
use App\Mail\PartnerInvitationMail;
use App\Models\ContactMessage\ContactMessage;
use App\Models\Partner\PartnerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Il mittente resta un no-reply: il Reply-To è l'unico modo perché la risposta
 * di un partner arrivi davvero a qualcuno.
 */
class DefaultReplyToTest extends TestCase
{
    use RefreshDatabase;

    private function application(): PartnerApplication
    {
        return PartnerApplication::create([
            'first_name' => 'Susanna',
            'last_name' => 'Rossi',
            'email' => 'info@hotelrosovino.it',
            'phone' => '3498798828',
            'city' => 'Milano',
            'business_name' => 'Hotel Rosovino',
            'role' => 'Titolare',
            'offer_type' => 'Struttura ricettiva',
            'description' => 'Hotel pet friendly.',
        ]);
    }

    public function test_the_invitation_carries_the_default_reply_to(): void
    {
        config(['mail.reply_to.address' => 'assistenza@animalamo.it']);

        Mail::to('info@hotelrosovino.it')
            ->send(new PartnerInvitationMail($this->application(), 'https://animalamo.it/x'));

        $sent = Mail::mailer()->getSymfonyTransport()->messages()[0]->getOriginalMessage();

        $this->assertSame('assistenza@animalamo.it', $sent->getReplyTo()[0]->getAddress());
    }

    /** Chi risponde a "Contattaci" deve scrivere al visitatore, e solo a lui. */
    public function test_the_contact_mail_keeps_its_own_reply_to(): void
    {
        config(['mail.reply_to.address' => 'assistenza@animalamo.it']);

        $message = ContactMessage::create([
            'first_name' => 'Marco',
            'last_name' => 'Bianchi',
            'email' => 'marco@example.com',
            'reason' => 'Informazioni',
            'message' => 'Buongiorno, vorrei sapere…',
        ]);

        Mail::to('animalamo24@gmail.com')->send(new ContactMessageMail($message));

        $sent = Mail::mailer()->getSymfonyTransport()->messages()[0]->getOriginalMessage();

        $this->assertCount(1, $sent->getReplyTo());
        $this->assertSame('marco@example.com', $sent->getReplyTo()[0]->getAddress());
    }
}
