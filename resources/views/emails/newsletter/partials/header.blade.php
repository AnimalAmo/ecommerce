{{--
    Testata delle mail della newsletter: quella di x-mail::header (stesso
    aspetto delle mail di servizio), più il preheader — la riga grigia che la
    casella mostra accanto all'oggetto. È il primo testo del corpo, nascosto.
--}}
<tr>
<td class="header">
@if (filled($preheader ?? null))
<div style="display: none; max-height: 0; overflow: hidden; mso-hide: all; font-size: 1px; line-height: 1px; color: #ffffff; opacity: 0;">{{ $preheader }}&#8203;&nbsp;&#8203;&nbsp;&#8203;&nbsp;&#8203;&nbsp;&#8203;&nbsp;&#8203;&nbsp;</div>
@endif
<a href="{{ $homeUrl }}" style="display: inline-block;">{{ config('app.name') }}</a>
</td>
</tr>
