<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Profilo — messaggi
    |--------------------------------------------------------------------------
    |
    | Toast e messaggi di validazione contestuali del profilo e della sicurezza,
    | tenuti qui (non nel validation.php generico) perché variano per contesto.
    |
    */

    'saved' => 'Modifiche salvate.',

    'email_in_use' => 'Questa email è già in uso.',
    'current_password_for_email' => 'Inserisci la password attuale per cambiare l\'email.',
    'current_password_required' => 'Inserisci la password attuale.',
    'current_password_incorrect' => 'La password attuale non è corretta.',
    'new_password_required' => 'Inserisci la nuova password.',

    /*
    |--------------------------------------------------------------------------
    | Profilo — UI (titoli tab, sidebar, heading, label, bottoni)
    |--------------------------------------------------------------------------
    */

    // Titoli tab del browser (->title del componente Livewire)
    'title_profile' => 'Profilo — AnimalAmo',
    'title_orders' => 'I miei ordini — AnimalAmo',
    'title_order_summary' => 'Riepilogo ordine — AnimalAmo',
    'title_payment' => 'Metodo di pagamento — AnimalAmo',
    'title_security' => 'Sicurezza — AnimalAmo',
    'title_events' => 'Eventi a cui partecipo — AnimalAmo',

    // Voci sidebar profilo
    'nav_profile' => 'Profilo',
    'nav_payment' => 'Metodo di pagamento',
    'nav_security' => 'Sicurezza',
    'nav_orders' => 'I miei ordini',
    'nav_events' => 'Eventi a cui partecipo',

    // Voci del menu mobile (XD app "Profilo": etichette più corte della sidebar desktop)
    'nav_personal_data' => 'Dati anagrafici',
    'nav_payment_data' => 'Dati pagamento',

    // Heading delle card
    'personal_info_title' => 'Informazioni personali',
    'orders_title' => 'I miei ordini',
    'order_summary_title' => 'Riepilogo ordine',
    'payment_title' => 'Informazioni del metodo di pagamento',
    'security_title' => 'Sicurezza e Privacy',
    'interests_title' => 'I miei interessi',

    // Bottoni / voci comuni
    'save' => 'Salva',
    'cancel' => 'Annulla',
    'confirm' => 'Conferma',
    'back' => 'Indietro',
    'no_results' => 'Nessun risultato',

    // Tab (In programma / Passati)
    'tab_upcoming' => 'In programma',
    'tab_past' => 'Passati',

    // Label dati personali
    'field_first_name' => 'Nome',
    'field_last_name' => 'Cognome',
    'field_birth_date' => 'Data di nascita',
    'field_email' => 'Email',
    'field_pet_type' => 'Tipologia animale',
    'field_address' => 'Indirizzo',
    'field_city' => 'Città',
    'field_zip' => 'Cap',
    'field_phone' => 'Cellulare',
    'field_current_password_email' => 'Password attuale (per cambiare l\'email)',

    // Metodo di pagamento
    'payment_card_holder' => 'Titolare carta',
    'payment_card_number' => 'Numero della carta',
    'payment_card_expiry' => 'Data di scadenza',
    'payment_card_cvv' => 'Codice di sicurezza',
    'payment_edit_card' => 'Modifica carta',
    'payment_remove_card' => 'Elimina carta',
    'payment_remove_confirm' => 'Vuoi eliminare la carta salvata?',
    'payment_card_saved' => 'Carta salvata correttamente',
    'payment_card_removed' => 'Carta eliminata',
    'payment_card_error' => 'Non è stato possibile salvare la carta. Riprova.',
    'payment_card_incomplete' => 'Completa i dati della carta per salvarla.',
    'payment_unavailable' => 'Il salvataggio della carta non è al momento disponibile. Riprova più tardi.',

    // Sicurezza e Privacy
    'current_password_label' => 'Password attuale',
    'password_label' => 'Password',
    'password_confirm_label' => 'Conferma Password',
    'reset_password' => 'Reimposta password',
    'privacy_settings' => 'Impostazioni sulla privacy',
    'delete_account' => 'Elimina account',

    // Riepilogo ordine / recensione
    'write_review' => 'Scrivi una recensione',
    'view_review' => 'Vedi recensione',
    'review_title_placeholder' => 'Titolo',
    'review_text_placeholder' => 'Recensione',
    'review_title_label' => 'Titolo',
    'review_text_label' => 'Descrizione',
    'review_share' => 'Condividi',
    'review_shared' => 'Recensione condivisa con successo!',
    'gift_dedicated_to' => 'Dedicato a: :name',
    'gift_message' => 'Messaggio: :message',

    // Eventi
    'attend' => 'Partecipa',

];
