<x-layouts.app title="Contact — Vikings" description="Phone numbers, addresses and opening hours for every Vikings restaurant.">
    <section class="page container">
        <h1>Contact</h1>
        <p class="lead-left">Call the restaurant nearest you, or book a table online.</p>

        @forelse ($locations as $location)
            <article class="location-card">
                <h2><a href="{{ route('locations.show', $location['slug']) }}">{{ $location['name'] }}</a></h2>
                <p class="muted">{{ $location['address']['full'] }}</p>
                <p><a href="tel:{{ preg_replace('/[^\d+]/', '', $location['phone']) }}">{{ $location['phone'] }}</a></p>
                <dl class="hours">
                    @foreach (\App\Support\HoursSummary::group($location['hours']) as $row)
                        <dt>{{ $row['days'] }}</dt>
                        <dd>{{ $row['hours'] }}</dd>
                    @endforeach
                </dl>
                <a href="{{ route('bookings.create', $location['slug']) }}" class="btn btn-sm">Book a table</a>
            </article>
        @empty
            <p class="muted">We are opening new locations soon. Check back shortly.</p>
        @endforelse
    </section>
</x-layouts.app>
