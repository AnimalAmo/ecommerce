<?php

/*
| Pannello di amministrazione — Incassi (registro dei pagamenti ai partner).
| Solo italiano: il pannello non è localizzato, quindi niente
| lang/en/admin-money.php (e il file non è in LangParityTest).
*/

return [
    'title' => 'Incassi',
    'subtitle' => 'Dal registro dei pagamenti ai partner. Ogni mattina il netto si conferma con Stripe e partono i bonifici maturati.',
    'period_label' => 'Periodo',
    'export' => 'Esporta',

    'period' => [
        'last_12_months' => 'Ultimi 12 mesi',
    ],

    'kpi' => [
        // "a settembre", ma "ad agosto": la vocale iniziale del mese sceglie la chiave.
        'gross_month' => 'Incassato a :month',
        'gross_month_vowel' => 'Incassato ad :month',
        'gross_range' => 'Incassato negli ultimi 12 mesi',
        'gross_note' => '{0} nessun ordine|{1} :count ordine|[2,*] :count ordini',
        'partners' => 'Andato ai partner',
        'partners_note' => ':percent% del venduto, dopo le commissioni Stripe',
        'partners_provisional' => '{1} :count riga col netto ancora da confermare|[2,*] :count righe col netto ancora da confermare',
        'platform' => 'Rimasto ad AnimalAmo',
        'platform_note' => 'provvigioni sulle vendite dei partner',
        'platform_direct' => 'provvigioni, più :amount di vendite dirette',
        'to_release' => 'Da liquidare',
        'to_release_none' => 'niente in sospeso',
        'to_release_next' => 'il primo matura il :date',
        'to_release_due' => 'maturo dal :date, in attesa del bonifico',
        'to_release_failed' => 'bonifici falliti da sbloccare',
        'empty_note' => '—',
    ],

    'notice' => [
        'stuck_heading' => '{1} Un bonifico è fermo|[2,*] :count bonifici sono fermi',
        'stuck_body' => 'Per :amount Stripe ha rifiutato il bonifico più volte, o ha risposto con un esito incerto: il sistema non riprova più da solo. Controlla l\'account del partner su Stripe, poi chiedi all\'assistenza tecnica di rimetterli in coda.',
        'unsplit_heading' => '{1} Un ordine non ha la divisione fra partner e AnimalAmo|[2,*] :count ordini non hanno la divisione fra partner e AnimalAmo',
        'unsplit_body' => 'Sono precedenti ai pagamenti divisi con Stripe Connect: il loro importo è nell\'incassato, ma non in quanto è andato ai partner o ad AnimalAmo.',
    ],

    'partners' => [
        'heading' => 'Quanto è andato a ciascun partner',
        'empty' => 'Nessuna vendita dei partner nel periodo.',
        'col_partner' => 'Partner',
        'col_gross' => 'Venduto',
        'col_net' => 'Al partner',
        'col_fee' => 'Ad AnimalAmo',
        'orders' => '{1} :count ordine|[2,*] :count ordini',
        'provisional' => 'netto provvisorio',
    ],

    'transfers' => [
        'heading' => 'Bonifici',
        'open_stripe' => 'Apri su Stripe',
        'open_account' => 'Apri l\'account su Stripe',
        'empty' => 'Nessun bonifico nel periodo.',
        'col_partner' => 'Destinatario',
        'col_date' => 'Data',
        'col_amount' => 'Importo',
        'col_status' => 'Stato',

        'state' => [
            'released' => 'Emesso',
            'scheduled' => 'In maturazione',
            'waiting' => 'In attesa',
            'retrying' => 'Nuovo tentativo',
            'failed' => 'Fallito',
        ],

        'reason' => [
            'no_account' => 'il partner non ha un account Stripe',
            'not_payable' => 'account Stripe non ancora abilitato ai bonifici',
            'provisional' => 'aspetta il netto confermato da Stripe',
            'next_run' => 'parte al prossimo giro del mattino',
        ],

        'attempt' => 'tentativo :attempts di :max',
        'uncertain' => 'esito incerto: verificare su Stripe prima di ritentare',
    ],

    'export_file' => [
        'col_order_date' => 'Data ordine',
        'col_order' => 'Ordine',
        'col_item' => 'Scheda',
        'col_partner' => 'Partner',
        'col_account' => 'Account Stripe',
        'col_gross' => 'Lordo',
        'col_fee' => 'Provvigione AnimalAmo',
        'col_net' => 'Netto al partner',
        'col_net_confirmed' => 'Netto confermato da Stripe',
        'col_status' => 'Stato',
        'col_release_at' => 'Matura il',
        'col_released_at' => 'Bonificato il',
        'col_payout' => 'Bonifico Stripe',
        'col_attempts' => 'Tentativi falliti',
        'col_error' => 'Ultimo errore',
        'yes' => 'sì',
        'no' => 'no',
        'unsplit' => 'Senza divisione (prima di Connect)',
        'platform' => 'AnimalAmo',

        'status' => [
            'pending' => 'Da bonificare',
            'released' => 'Bonificato',
            'reversed' => 'Stornato',
            'failed' => 'Fallito',
            'platform_only' => 'Vendita diretta AnimalAmo',
        ],
    ],
];
