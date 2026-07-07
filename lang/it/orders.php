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
    ],
    // Profilo — i miei ordini: conteggio articoli della riga lista
    'items_count' => '{1} 1 articolo|[2,*] :count articoli',
    // Label OrderStatus
    'status' => [
        'cancelled' => 'Annullato',
        'paid' => 'Pagato',
        'pending' => 'In attesa',
    ],
];
