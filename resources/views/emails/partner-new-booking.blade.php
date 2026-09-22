@use('App\Support\Format')
@use('App\Services\Pricing\BookingPricingService')
<x-mail::message>
# {{ __('orders.mail.partner_booking.title') }}

{{ __('orders.mail.confirmation.greeting', ['name' => $partner->first_name]) }}

{{ __('orders.mail.partner_booking.intro', ['order_number' => $order->order_number, 'date' => Format::dateShort($order->created_at ?? now())]) }}

<x-mail::table>
| {{ __('orders.mail.confirmation.table_item') }} | {{ __('orders.mail.partner_booking.table_people') }} | {{ __('orders.mail.confirmation.table_price') }} |
|:--|:-:|--:|
@foreach ($lines as $item)
| **{{ $item->title }}**@if ($item->purchasable_type !== 'smartbox_package' && $item->booked_from !== null && $item->booked_until !== null)<br>{{ Format::dateRange($item->booked_from, $item->booked_until) }}@endif | {{ BookingPricingService::persons($item->options ?? []) }} | {{ Format::money($item->price_cents) }} |
@endforeach
| **{{ __('orders.mail.confirmation.total') }}** | | **{{ Format::money($linesTotal) }}** |
</x-mail::table>

@if ($order->isOnSite())
**{{ __('orders.mail.partner_booking.to_collect', ['amount' => Format::money($linesTotal)]) }}**
@else
**{{ __('orders.mail.partner_booking.paid_online') }}**
@endif

<x-mail::button :url="$link">
{{ __('orders.mail.partner_booking.cta') }}
</x-mail::button>

{{ __('orders.mail.confirmation.closing') }}<br>
{{ __('orders.mail.confirmation.signature') }}
</x-mail::message>
