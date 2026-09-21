{{--
    Piede delle mail della newsletter: perché la ricevi e come smettere (solo
    negli invii veri: la mail di conferma non ha nulla da cui disiscriversi),
    poi chi scrive — denominazione e P.IVA, come nel piede del sito.
--}}
<x-mail::footer>
@if (filled($unsubscribeUrl ?? null))
{{ __('newsletter.mail.layout.reason') }} [{{ __('newsletter.mail.layout.unsubscribe') }}]({{ $unsubscribeUrl }})

@endif
© {{ date('Y') }} Animal Amo Srl · P.IVA 02746270228 · [{{ __('newsletter.mail.layout.privacy') }}]({{ $privacyUrl }})
</x-mail::footer>
