<?php

return [
    'page_title' => 'Contattaci',
    'heading' => 'Contattaci',
    'intro' => 'Hai bisogno di informazioni o di assistenza? Compila il form e ti risponderemo il prima possibile.',

    'first_name' => 'Nome',
    'last_name' => 'Cognome',
    'email' => 'Email',
    'reason' => 'Motivo del contatto',
    'reason_info' => 'Informazioni generali',
    'reason_support' => 'Assistenza su un ordine',
    'reason_partner' => 'Collaborazioni e partnership',
    'reason_other' => 'Altro',
    'select_placeholder' => 'Seleziona…',
    'message' => 'Messaggio',
    'message_placeholder' => 'Scrivici come possiamo aiutarti…',
    'submit' => 'Invia messaggio',

    'thanks_heading' => 'Grazie!',
    'thanks_line_1' => 'Abbiamo ricevuto il tuo messaggio.',
    'thanks_line_2' => 'Ti risponderemo il prima possibile.',

    'map_alt' => 'Mappa della sede — Animal Amo Srl',
    'info_heading' => 'I nostri contatti',
    'info_email_title' => 'Email informazioni',
    'info_instagram_title' => 'Instagram',

    // Copy della notifica interna (ContactMessageMail): la legge la cliente,
    // non l'utente finale, ma passa comunque da __() come tutto il resto.
    'notification_mail' => [
        'subject' => 'Nuovo messaggio da Contattaci — :reason',
        'title' => 'Nuovo messaggio dal sito',
        'intro' => 'Qualcuno ha compilato il form Contattaci scegliendo ":reason".',
        'field_name' => 'Nome',
        'field_email' => 'Email',
        'field_reason' => 'Motivo',
        'field_message' => 'Messaggio',
        'outro' => 'Rispondi pure a questa email: la risposta arriva direttamente a chi ha scritto.',
        'signature' => 'AnimalAmo',
    ],
];
