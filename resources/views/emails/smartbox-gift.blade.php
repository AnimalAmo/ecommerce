@use('App\Support\Format')
<x-mail::message>
# {{ __('orders.mail.gift.title') }}

{{ __('orders.mail.gift.intro', ['buyer' => $buyerName, 'title' => $item->title]) }}

@if ($dedication !== null)
{{ __('orders.mail.gift.dedication', ['dedication' => $dedication]) }}
@endif

@if ($giftMessage !== null)
{{ __('orders.mail.gift.message', ['message' => $giftMessage]) }}
@endif

@if ($item->booked_until !== null)
{{ __('orders.mail.gift.validity', ['date' => Format::dateShort($item->booked_until)]) }}
@endif

{{ __('orders.mail.gift.closing') }}<br>
{{ __('orders.mail.gift.signature') }}
</x-mail::message>
