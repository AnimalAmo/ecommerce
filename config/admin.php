<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Approvazione preventiva delle pubblicazioni
    |--------------------------------------------------------------------------
    | Spenta: il partner che chiude il wizard va subito a catalogo (com'è
    | sempre stato). Accesa: una scheda NUOVA entra "in attesa" e compare sul
    | sito solo dopo "Approva e pubblica" dal pannello; una scheda rimandata
    | con "Chiedi modifiche" torna in attesa quando il partner la ripubblica.
    | Le schede già approvate restano online anche quando il partner le
    | modifica.
    |
    | Cambia l'abitudine dei partner: va annunciato prima di accenderla.
    */

    'moderation' => (bool) env('ADMIN_MODERATION', false),

    /*
    |--------------------------------------------------------------------------
    | Fuso orario del pannello
    |--------------------------------------------------------------------------
    | L'applicazione e il database ragionano in UTC; la cliente ragiona in ora
    | italiana. Un ordine delle 00:30 del primo del mese è di quel mese, non
    | del precedente, e alle 14 il saluto è "buon pomeriggio". Vale per i
    | periodi di Home e Incassi e per il saluto, non per i dati salvati.
    */

    'timezone' => 'Europe/Rome',

];
