<x-mail::message>
# {{ __('moderation.mail.heading', ['name' => $partnerName]) }}

{{ __("moderation.mail.{$outcome}.intro", ['name' => $itemName]) }}

@if ($outcome === \App\Mail\CatalogModerationMail::CHANGES_REQUESTED && filled($note))
{{ __('moderation.mail.changes_requested.note_heading') }}

<x-mail::panel>
{{ $note }}
</x-mail::panel>
@endif

<x-mail::button :url="$link">
{{ __("moderation.mail.{$outcome}.cta") }}
</x-mail::button>

{{ __('moderation.mail.signature') }}<br>
AnimalAmo
</x-mail::message>
