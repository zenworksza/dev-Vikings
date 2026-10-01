<x-layouts.app :title="'Booking request — '.$booking->location_name">
    @push('head') <meta name="robots" content="noindex"> @endpush
    <section class="page container">
        <h1>Booking request</h1>
        <p class="lead-left">{{ $booking->location_name }}</p>

        <div class="location-card">
            <h2>{{ $booking->party_size }} {{ \Illuminate\Support\Str::plural('guest', $booking->party_size) }} &middot; {{ $booking->starts_at->format('l j F Y, H:i') }}</h2>
            <p>{{ $booking->customer_name }} &middot; <a href="tel:{{ preg_replace('/[^\d+]/', '', $booking->customer_phone) }}">{{ $booking->customer_phone }}</a> &middot; <a href="mailto:{{ $booking->customer_email }}">{{ $booking->customer_email }}</a></p>
            @if ($booking->notes)
                <p class="muted">Notes: {{ $booking->notes }}</p>
            @endif
        </div>

        @if ($booking->isPending() && $booking->starts_at->isFuture())
            <form method="POST" action="{{ $confirmUrl }}" class="inline-form">
                @csrf
                <button type="submit" class="btn">Confirm booking</button>
            </form>

            <form method="POST" action="{{ $declineUrl }}" class="form-card">
                @csrf
                <label>Reason for declining (optional, sent to the customer)
                    <input type="text" name="reason" maxlength="300">
                </label>
                <button type="submit" class="btn btn-quiet">Decline booking</button>
            </form>
        @else
            <p class="status">
                @if ($booking->isPending()) This booking time has passed.
                @else This booking is <strong>{{ $booking->status->value }}</strong>@if ($booking->decided_at) ({{ $booking->decided_at->format('j M, H:i') }})@endif.
                @endif
            </p>
        @endif
    </section>
</x-layouts.app>
