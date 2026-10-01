Hi {{ $booking->customer_name }},

Thanks for your booking request at {{ $booking->location_name }}. This is a request, not yet a confirmed table: the restaurant will confirm by email.

{{ config('site.booking_not_confirmed_notice') }}

Table for {{ $booking->party_size }}
{{ $booking->starts_at->format(config('site.datetime_format')) }}

You can check the status or cancel here:
{{ $url }}

— {{ config('site.name') }}
