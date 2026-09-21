<x-mail::layout>
<x-slot:header>
@include('emails.newsletter.partials.header')
</x-slot:header>

# {{ __('newsletter.mail.confirm.heading') }}

{{ $courtesy ? __('newsletter.mail.confirm.courtesy_intro') : __('newsletter.mail.confirm.intro') }}

<x-mail::button :url="$confirmUrl">
{{ __('newsletter.mail.confirm.cta') }}
</x-mail::button>

{{ $courtesy ? __('newsletter.mail.confirm.courtesy_ignore', ['days' => $ttlDays]) : __('newsletter.mail.confirm.ignore', ['days' => $ttlDays]) }}

<x-slot:subcopy>
<x-mail::subcopy>
{{ __('newsletter.mail.layout.button_fallback', ['action' => __('newsletter.mail.confirm.cta')]) }} <span class="break-all">[{{ $confirmUrl }}]({{ $confirmUrl }})</span>
</x-mail::subcopy>
</x-slot:subcopy>

<x-slot:footer>
@include('emails.newsletter.partials.footer')
</x-slot:footer>
</x-mail::layout>
