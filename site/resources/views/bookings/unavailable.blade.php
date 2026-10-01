<x-layouts.app :title="'Book a table — '.$location['name']">
    @push('head') <meta name="robots" content="noindex"> @endpush
    <section class="page container">
        <h1>Book a table</h1>
        <p class="lead-left">Online booking is not available for {{ $location['name'] }} yet. Please call us on
            <a href="tel:{{ preg_replace('/[^\d+]/', '', $location['phone']) }}">{{ $location['phone'] }}</a>.</p>
        <p><a href="{{ route('locations.show', $location['slug']) }}">&larr; Back to {{ $location['name'] }}</a></p>
    </section>
</x-layouts.app>
