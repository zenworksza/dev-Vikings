@php
    $seating = $location['seating'];
    $special = $location['special_days'];
@endphp
<x-layouts.app
    :title="$location['name'].' — Vikings'"
    :description="'Visit '.$location['name'].' at '.$location['address']['full'].'. Opening hours, seating and contact details.'">
    <section class="page container">
        <p class="eyebrow"><a href="{{ route('locations.index') }}">&larr; All locations</a></p>
        <h1>{{ $location['name'] }}</h1>

        @if ($location['description'])
            <p class="lead-left">{!! nl2br(e($location['description'])) !!}</p>
        @endif

        <div class="detail-grid">
            <div>
                <h2 class="h-sm">Find us</h2>
                <p>{{ $location['address']['full'] }}</p>
                <p><a href="tel:{{ preg_replace('/[^\d+]/', '', $location['phone']) }}">{{ $location['phone'] }}</a></p>
                @if ($seating['seats'])
                    <p class="muted">
                        Seats up to {{ $seating['seats'] }} guests
                        @if ($seating['tables']) across {{ $seating['tables'] }} tables @endif
                    </p>
                @endif
            </div>

            <div>
                <h2 class="h-sm">Opening hours</h2>
                <dl class="hours">
                    @foreach (\App\Support\HoursSummary::group($location['hours']) as $row)
                        <dt>{{ $row['days'] }}</dt>
                        <dd>{{ $row['hours'] }}</dd>
                    @endforeach
                </dl>

                @if ($special)
                    <h2 class="h-sm">Special days</h2>
                    <dl class="hours">
                        @foreach ($special as $day)
                            <dt>{{ \Illuminate\Support\Carbon::parse($day['date'])->format('j M Y') }}@if ($day['label']) · {{ $day['label'] }}@endif</dt>
                            <dd>{{ $day['closed'] ? 'Closed' : $day['opens_at'].' – '.$day['closes_at'] }}</dd>
                        @endforeach
                    </dl>
                @endif
            </div>
        </div>
    </section>
</x-layouts.app>
