<x-mail::message>
# {{ __('auth-modal.reset_mail.heading', ['name' => $user->first_name]) }}

{{ __('auth-modal.reset_mail.intro') }}

<x-mail::button :url="$link">
{{ __('auth-modal.reset_mail.cta') }}
</x-mail::button>

{{ __('auth-modal.reset_mail.expiry', ['count' => $expiresInMinutes]) }}

{{ __('auth-modal.reset_mail.ignore') }}

{{ __('auth-modal.reset_mail.signature') }}<br>
AnimalAmo
</x-mail::message>
