@php
    use App\Enums\BookingStatus;
    $open = $booking->isOpen();
@endphp
<x-layouts.app :title="'Your booking — '.$booking->location_name">
    @push('head') <meta name="robots" content="noindex"> @endpush
    <section class="page container">
        <h1>Your booking</h1>

        @if (session('submitted'))
            <p class="notice">Thanks, {{ $booking->customer_name }}. We have sent you an email; bookmark this page to check on your request.</p>
        @endif

        <div class="location-card">
            <h2>{{ $booking->location_name }}</h2>
            <p>Table for {{ $booking->party_size }} &middot; {{ $booking->starts_at->format(config('site.datetime_format')) }}</p>

            @switch($booking->status)
                @case(BookingStatus::Pending)
                    <p class="status status-pending">Waiting for the restaurant to confirm. Your seats are held in the meantime.</p>
                    @break
                @case(BookingStatus::Confirmed)
                    <p class="status status-confirmed">Confirmed. See you then.</p>
                    @break
                @case(BookingStatus::Declined)
                    <p class="status status-declined">The restaurant could not take this booking.@if ($booking->decline_reason) Reason: {{ $booking->decline_reason }}@endif</p>
                    @break
                @case(BookingStatus::Cancelled)
                    <p class="status">This booking was cancelled.</p>
                    @break
            @endswitch

            @if ($open)
                <form method="POST" action="{{ route('bookings.cancel', $booking) }}">
                    @csrf
                    <button type="submit" class="btn btn-quiet">Cancel booking</button>
                </form>
            @endif
        </div>

        <p><a href="{{ route('locations.show', $booking->location_slug) }}">&larr; {{ $booking->location_name }}</a></p>
    </section>
</x-layouts.app>
