@component('mail::message')
# Промена на статус на нарачка

Вашата нарачка **{{ $order->order_number }}**  
е променета од **{{ $fromStatus }}** во **{{ $toStatus }}**.

@component('mail::table')
| Производ | Кол. | Вкупно |
|:--|--:|--:|
@foreach($order->items as $item)
| {{ $item->product_name }} | {{ $item->qty }} | {{ number_format($item->line_total_den, 0, '.', ',') }} ден |
@endforeach
@endcomponent

**Вкупно:** {{ number_format($order->total_den, 0, '.', ',') }} ден

@if($order->address)
## Адреса
{{ $order->address->full_name }}  
{{ $order->address->city }}, {{ $order->address->address_line1 }}
@endif

@component('mail::button', ['url' => config('app.url')])
Отвори продавница
@endcomponent

Поздрав,  
{{ config('app.name') }}
@endcomponent
