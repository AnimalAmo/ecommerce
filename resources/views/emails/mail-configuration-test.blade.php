<x-mail::message>
# Configurazione email funzionante

Questa è una mail di prova inviata da **{{ config('app.name') }}**.

Se la stai leggendo, il portale riesce a consegnare le email con il mailer configurato:
ordini, inviti partner e regali Smartbox useranno lo stesso canale.

<x-mail::table>
| Parametro   | Valore                |
|:------------|:----------------------|
| Mailer      | {{ $mailer }}         |
| Ambiente    | {{ $environment }}    |
| URL app     | {{ $appUrl }}         |
| Inviata il  | {{ $sentAt }}         |
</x-mail::table>

Nessuna azione richiesta: il messaggio è generato dal comando `php artisan mail:test`.

AnimalAmo
</x-mail::message>
