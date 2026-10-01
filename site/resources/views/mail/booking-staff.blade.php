New table booking request — {{ $booking->location_name }}

{{ $booking->customer_name }} would like a table for {{ $booking->party_size }}.

When:   {{ $booking->starts_at->format(config('site.datetime_format')) }} (until about {{ $booking->ends_at->format('H:i') }})
Name:   {{ $booking->customer_name }}
Phone:  {{ $booking->customer_phone }}
Email:  {{ $booking->customer_email }}
@if ($booking->notes)
Notes:  {{ $booking->notes }}
@endif

The seats are held for them until you answer. Confirm or decline here (no login needed):

{{ $reviewUrl }}

You can also reply to this email to reach the customer directly.
