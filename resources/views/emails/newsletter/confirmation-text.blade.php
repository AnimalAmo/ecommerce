{{-- Parte text/plain: niente {{ }}, l'escape HTML lascerebbe &#039; al posto degli apostrofi. --}}
{!! __('newsletter.mail.confirm.heading') !!}

{!! $courtesy ? __('newsletter.mail.confirm.courtesy_intro') : __('newsletter.mail.confirm.intro') !!}

{!! __('newsletter.mail.confirm.cta') !!}: {!! $confirmUrl !!}

{!! $courtesy ? __('newsletter.mail.confirm.courtesy_ignore', ['days' => $ttlDays]) : __('newsletter.mail.confirm.ignore', ['days' => $ttlDays]) !!}

--
© {{ date('Y') }} Animal Amo Srl - P.IVA 02746270228
{!! __('newsletter.mail.layout.privacy') !!}: {!! $privacyUrl !!}
