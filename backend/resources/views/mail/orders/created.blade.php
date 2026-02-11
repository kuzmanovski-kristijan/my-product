@component('mail::message')
# Ви благодариме за нарачката 🎉

Вашиот број на нарачка е: **{{ $order->order_number }}**  
Статус: **{{ $order->status }}**  
Плаќање: **{{ $order->payment_method }}**

## Детали
@component('mail::table')
| Производ | Варијанта | Кол. | Цена | Вкупно |
|:--|:--|--:|--:|--:|
@foreach($order->items as $item)
| {{ $item->product_name }} | {{ $item->variant_name ?? '-' }} | {{ $item->qty }} | {{ number_format($item->unit_price_den, 0, '.', ',') }} ден | {{ number_format($item->line_total_den, 0, '.', ',') }} ден |
@endforeach
@endcomponent

**Меѓузбир:** {{ number_format($order->subtotal_den, 0, '.', ',') }} ден  
**Достава:** {{ number_format($order->shipping_den, 0, '.', ',') }} ден  
**Вкупно:** **{{ number_format($order->total_den, 0, '.', ',') }} ден**

## Адреса за испорака
**{{ $order->address->full_name }}**  
{{ $order->address->city }}, {{ $order->address->address_line1 }}  
@if($order->address->address_line2) {{ $order->address->address_line2 }} @endif  
Тел: {{ $order->address->phone }}

@if($order->customer_note)
## Забелешка
{{ $order->customer_note }}
@endif

@component('mail::button', ['url' => config('app.url')])
Отвори продавница
@endcomponent

Поздрав,  
{{ config('app.name') }}
@endcomponent
