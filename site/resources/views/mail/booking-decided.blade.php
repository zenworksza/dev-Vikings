Hi {{ $booking->customer_name }},

@if ($confirmed)
Good news: your table at {{ $booking->location_name }} is confirmed.

Table for {{ $booking->party_size }}
{{ $booking->starts_at->format('l j F Y, H:i') }}

If your plans change, please cancel here so we can free the table:
{{ $url }}
@else
Unfortunately {{ $booking->location_name }} could not take your booking for {{ $booking->party_size }} on {{ $booking->starts_at->format('l j F Y, H:i') }}.
@if ($booking->decline_reason)

Reason: {{ $booking->decline_reason }}
@endif

You are welcome to try another time:
{{ route('locations.index') }}
@endif

— {{ config('site.name') }}
