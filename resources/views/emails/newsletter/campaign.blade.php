<x-mail::layout>
<x-slot:header>
@include('emails.newsletter.partials.header')
</x-slot:header>

@if ($isTest)
<x-mail::panel>
{{ __('newsletter.mail.layout.test_notice') }}
</x-mail::panel>

@endif
{{-- Corpo dall'editor del pannello, già ripulito da HtmlSanitizer al salvataggio.
     Su una riga sola: per il parser markdown del layout è un unico blocco HTML,
     che passa intatto (una riga vuota lo spezzerebbe). --}}
{!! $bodyHtml !!}

<x-slot:footer>
@include('emails.newsletter.partials.footer')
</x-slot:footer>
</x-mail::layout>
