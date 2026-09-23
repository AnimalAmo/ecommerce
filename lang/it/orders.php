<?php

return [
    // Copy delle email transazionali (OrderConfirmationMail / SmartboxGiftMail)
    'mail' => [
        'confirmation' => [
            'subject' => 'Conferma ordine :order_number',
            'title' => 'Grazie del tuo acquisto!',
            'greeting' => 'Ciao :name,',
            'intro' => 'il tuo ordine :order_number è confermato: ecco il riepilogo.',
            'table_item' => 'Prodotto',
            'table_price' => 'Prezzo',
            'gift_flag' => '(regalo)',
            'total' => 'Totale',
            'payment' => 'Metodo di pagamento: :method',
            'outro' => 'Per qualsiasi domanda sul tuo ordine rispondi pure a questa email.',
            'closing' => 'A presto',
            'signature' => 'Il team AnimalAmo',
        ],
        'gift' => [
            'subject' => ':buyer ti ha regalato una Smartbox',
            'title' => 'Hai ricevuto un regalo!',
            'intro' => ':buyer ti ha regalato la Smartbox ":title" su AnimalAmo.',
            'dedication' => 'Dedicato a: :dedication',
            'message' => 'Messaggio: :message',
            'validity' => 'La Smartbox è valida fino al :date.',
            'closing' => 'A presto',
            'signature' => 'Il team AnimalAmo',
        ],
        // Variante "da pagare in struttura" di OrderConfirmationMail (saluto, tabella e chiusura restano in confirmation)
        'on_site' => [
            'subject' => 'Prenotazione confermata :order_number',
            'title' => 'Prenotazione confermata!',
            'intro' => 'la tua prenotazione :order_number è confermata: ecco il riepilogo. Su AnimalAmo non hai pagato nulla, il pagamento avviene direttamente con il partner.',
            'amount_due' => 'Da pagare direttamente al partner :partner, in struttura o sul suo sito: :amount',
            // Stessa riga quando il partner non ha né ragione sociale né nome (o il profilo non c'è più)
            'amount_due_without_partner' => 'Da pagare direttamente al partner, in struttura o sul suo sito: :amount',
            'pay_on_website' => 'Paga sul sito del partner',
            'partner' => 'Indirizzo: :name, :address',
            // Indirizzo senza nome del partner
            'address' => 'Indirizzo: :address',
        ],
        // Mail al partner a ogni nuova prenotazione (PartnerNewBookingMail), online e in struttura
        'partner_booking' => [
            'subject' => 'Nuova prenotazione :order_number',
            'title' => 'Hai una nuova prenotazione!',
            // Saluto quando il partner non ha né nome né ragione sociale
            'greeting_without_name' => 'Ciao,',
            'intro' => 'un cliente ha prenotato su AnimalAmo: ordine :order_number del :date. Ecco il dettaglio.',
            'table_people' => 'Persone',
            'paid_online' => 'Pagato online',
            'to_collect' => 'Da incassare tu: :amount',
            'cta' => 'Vedi la prenotazione',
        ],
    ],
    // Profilo — i miei ordini: conteggio articoli della riga lista
    'items_count' => '{1} 1 articolo|[2,*] :count articoli',
    // Label OrderStatus
    'status' => [
        'cancelled' => 'Annullato',
        'confirmed' => 'Confermato',
        'paid' => 'Pagato',
        'pending' => 'In attesa',
    ],
    // Label OrderPaymentMode
    'payment_mode' => [
        'online' => 'Pagamento online',
        'on_site' => 'Pagamento in struttura',
    ],
];
