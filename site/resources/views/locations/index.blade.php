<x-layouts.app title="Our locations — Vikings" description="Find a Vikings restaurant near you: addresses, opening hours and seating.">
    <section class="page container">
        <h1>Our locations</h1>
        <p class="lead-left">Pull up a chair at the hearth nearest you.</p>

        @forelse ($locations as $location)
            <article class="location-card">
                <h2><a href="{{ route('locations.show', $location['slug']) }}">{{ $location['name'] }}</a></h2>
                <p class="muted">{{ $location['address']['full'] }}</p>
                @if ($location['seating']['seats'])
                    <p class="muted">Seats up to {{ $location['seating']['seats'] }} guests</p>
                @endif
                <a href="{{ route('locations.show', $location['slug']) }}" class="btn btn-sm">View location</a>
            </article>
        @empty
            <p class="muted">We are opening new locations soon. Check back shortly.</p>
        @endforelse
    </section>
</x-layouts.app>
