{{ $booking->customer_name }} has cancelled their booking at {{ $booking->location_name }}.

Table for {{ $booking->party_size }}
{{ $booking->starts_at->format(config('site.datetime_format')) }}
Phone: {{ $booking->customer_phone }}

The seats are free again; no action needed.
