<x-mail::message>
# {{ __('partner.welcome_mail.title', ['name' => $partner->first_name]) }}

@if ($setPasswordUrl !== null)
{{ __('partner.welcome_mail.intro', ['business' => $businessName]) }}

<x-mail::button :url="$setPasswordUrl">
{{ __('partner.welcome_mail.set_password_cta') }}
</x-mail::button>

{{ __('partner.welcome_mail.expires', ['days' => $expiresInDays]) }}
@else
{{ __('partner.welcome_mail.promoted_intro', ['business' => $businessName]) }}

<x-mail::button :url="$loginUrl">
{{ __('partner.welcome_mail.login_cta') }}
</x-mail::button>
@endif

{{ __('partner.invitation_mail.signature') }}<br>
AnimalAmo
</x-mail::message>
