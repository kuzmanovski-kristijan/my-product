@component('mail::message')
# Потврда за закажан термин ✅

Ви потврдуваме термин:

- Име: **{{ $appointment->full_name }}**
- Телефон: **{{ $appointment->phone }}**
- Локација: **{{ $appointment->store->name }}**, {{ $appointment->store->city }}
@if($appointment->store->address)
- Адреса: {{ $appointment->store->address }}
@endif
- Почеток: **{{ $appointment->starts_at->format('Y-m-d H:i') }}**
- Крај: **{{ $appointment->ends_at->format('H:i') }}**
- Статус: **{{ $appointment->status }}**

@if($appointment->note)
## Забелешка
{{ $appointment->note }}
@endif

Поздрав,  
{{ config('app.name') }}
@endcomponent
