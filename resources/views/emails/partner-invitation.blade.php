<x-mail::message>
# {{ __('partner.invitation_mail.heading', ['name' => $application->first_name]) }}

{{ __('partner.invitation_mail.intro', ['business' => $application->business_name]) }}

{{ __('partner.invitation_mail.cta_hint') }}

<x-mail::button :url="$link">
{{ __('partner.invitation_mail.cta') }}
</x-mail::button>

{{ __('partner.invitation_mail.outro') }}

{{ __('partner.invitation_mail.signature') }}<br>
AnimalAmo
</x-mail::message>
