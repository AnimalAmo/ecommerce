<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Righe del broker "password reset"
    |--------------------------------------------------------------------------
    |
    | Le chiavi rispecchiano le costanti di Illuminate\Support\Facades\Password.
    | Nel flusso pubblico mostriamo solo 'sent' e 'token': 'user' e 'throttled'
    | rivelerebbero a un ospite se un'email è registrata (vedi
    | PasswordResetService), e restano qui per completezza del framework.
    |
    */

    'reset' => 'La tua password è stata reimpostata.',
    'sent' => 'Ti abbiamo inviato via email il link per reimpostare la password.',
    'throttled' => 'Attendi qualche istante prima di riprovare.',
    'token' => 'Il link per reimpostare la password è scaduto o è già stato usato.',
    'user' => 'Nessun account risulta associato a questo indirizzo email.',

];
