<x-mail::message>
# {{ __('auth-modal.admin_reset_mail.heading', ['name' => $user->first_name]) }}

{{ __('auth-modal.admin_reset_mail.intro') }}

<x-mail::button :url="$link">
{{ __('auth-modal.admin_reset_mail.cta') }}
</x-mail::button>

{{ __('auth-modal.admin_reset_mail.expiry', ['days' => $expiresInDays]) }}

{{ __('auth-modal.admin_reset_mail.ignore') }}

{{ __('auth-modal.reset_mail.signature') }}<br>
AnimalAmo
</x-mail::message>
