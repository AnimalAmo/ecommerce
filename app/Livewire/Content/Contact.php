<?php

namespace App\Livewire\Content;

use App\Livewire\Forms\ContactForm;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage\ContactMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Throwable;

/**
 * Contattaci: unifica i vecchi link "Contattaci" e "Assistenza" (footer B2C,
 * footer e menu Aiuto partner) in un'unica pagina con form e dati di contatto.
 */
class Contact extends Component
{
    public ContactForm $form;

    public bool $showConfirmation = false;

    /** Salva il messaggio e notifica la casella informazioni. */
    public function submit(): void
    {
        $this->form->validate();

        $message = ContactMessage::create($this->form->toMessage());

        $this->notifySite($message);

        $this->showConfirmation = true;
    }

    /** Chiudendo la conferma il form riparte vuoto: il messaggio è già salvato. */
    public function closeConfirmation(): void
    {
        $this->showConfirmation = false;

        $this->form->reset();
    }

    public function render()
    {
        return view('livewire.content.contact')->title(__('contact.page_title'));
    }

    /**
     * Notifica non bloccante, stessa scelta di SendOrderPaidMails::sendSilently():
     * la riga contact_messages è già scritta e resta la fonte di verità, quindi
     * un destinatario non configurato o un mailer che esplode (SMTP giù, Mailgun
     * in errore) deve finire nei log, non in una schermata di errore su una
     * richiesta d'informazioni che abbiamo già preso in carico.
     */
    private function notifySite(ContactMessage $message): void
    {
        $recipient = config('mail.contact_recipient');

        if (blank($recipient)) {
            Log::warning('CONTACT_RECIPIENT non configurato: messaggio salvato ma non notificato', [
                'contact_message_id' => $message->id,
            ]);

            return;
        }

        try {
            Mail::to($recipient)->send(new ContactMessageMail($message));
        } catch (Throwable $exception) {
            report($exception);

            Log::warning('Notifica Contattaci non inviata, il messaggio resta a db', [
                'contact_message_id' => $message->id,
                'recipient' => $recipient,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
