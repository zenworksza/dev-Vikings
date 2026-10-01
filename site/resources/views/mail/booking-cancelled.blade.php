{{ $booking->customer_name }} has cancelled their booking at {{ $booking->location_name }}.

Table for {{ $booking->party_size }}
{{ $booking->starts_at->format('l j F Y, H:i') }}
Phone: {{ $booking->customer_phone }}

The seats are free again; no action needed.
