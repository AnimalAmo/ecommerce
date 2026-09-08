<x-mail::message>
# {{ __('contact.notification_mail.title') }}

{{ __('contact.notification_mail.intro', ['reason' => $contactMessage->reason]) }}

<x-mail::table>
| | |
|:--|:--|
| **{{ __('contact.notification_mail.field_name') }}** | {{ $contactMessage->first_name }} {{ $contactMessage->last_name }} |
| **{{ __('contact.notification_mail.field_email') }}** | {{ $contactMessage->email }} |
| **{{ __('contact.notification_mail.field_reason') }}** | {{ $contactMessage->reason }} |
</x-mail::table>

**{{ __('contact.notification_mail.field_message') }}**

{{ $contactMessage->message }}

{{ __('contact.notification_mail.outro') }}

{{ __('contact.notification_mail.signature') }}
</x-mail::message>
