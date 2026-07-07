@use('App\Support\Format')
<x-mail::message>
# {{ __('orders.mail.confirmation.title') }}

{{ __('orders.mail.confirmation.greeting', ['name' => $order->first_name]) }}

{{ __('orders.mail.confirmation.intro', ['order_number' => $order->order_number]) }}

<x-mail::table>
| {{ __('orders.mail.confirmation.table_item') }} | {{ __('orders.mail.confirmation.table_price') }} |
|:--|--:|
@foreach ($order->items as $item)
| **{{ $item->title }}**{{ $item->is_gift ? ' '.__('orders.mail.confirmation.gift_flag') : '' }}@if ($item->purchasable_type !== 'smartbox_package' && $item->booked_from !== null && $item->booked_until !== null)<br>{{ Format::dateRange($item->booked_from, $item->booked_until) }}@endif | {{ Format::money($item->price_cents) }} |
@endforeach
| **{{ __('orders.mail.confirmation.total') }}** | **{{ Format::money($order->total_cents) }}** |
</x-mail::table>

@if ($order->payment !== null)
{{ __('orders.mail.confirmation.payment', ['method' => $order->payment->payment_method->label()]) }}
@endif

{{ __('orders.mail.confirmation.outro') }}

{{ __('orders.mail.confirmation.closing') }}<br>
{{ __('orders.mail.confirmation.signature') }}
</x-mail::message>
