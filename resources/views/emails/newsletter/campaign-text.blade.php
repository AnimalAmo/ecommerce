{{-- Parte text/plain: niente {{ }}, l'escape HTML lascerebbe &#039; al posto degli apostrofi. --}}
@if ($isTest)
{!! __('newsletter.mail.layout.test_notice') !!}

@endif
{!! $bodyText !!}

--
@if (filled($unsubscribeUrl))
{!! __('newsletter.mail.layout.reason') !!}
{!! __('newsletter.mail.layout.unsubscribe') !!}: {!! $unsubscribeUrl !!}
@endif
© {{ date('Y') }} Animal Amo Srl - P.IVA 02746270228
{!! __('newsletter.mail.layout.privacy') !!}: {!! $privacyUrl !!}
