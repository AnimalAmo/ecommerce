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

];
