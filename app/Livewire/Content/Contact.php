<?php

namespace App\Livewire\Content;

use App\Livewire\Forms\ContactForm;
use App\Models\ContactMessage\ContactMessage;
use Livewire\Component;

/**
 * Contattaci: unifica i vecchi link "Contattaci" e "Assistenza" (footer B2C,
 * footer e menu Aiuto partner) in un'unica pagina con form e dati di contatto.
 */
class Contact extends Component
{
    public ContactForm $form;

    public bool $showConfirmation = false;

    /** Salva il messaggio; l'inoltro alla casella informazioni arriverà con la configurazione mail. */
    public function submit(): void
    {
        $this->form->validate();

        ContactMessage::create($this->form->toMessage());

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
}
