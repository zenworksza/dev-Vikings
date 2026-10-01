<x-layouts.app :title="'Book a table — '.$location['name']" :description="'Request a table at '.$location['name'].'.'">
    @push('head') <meta name="robots" content="noindex"> @endpush
    <section class="page container">
        <p class="eyebrow"><a href="{{ route('locations.show', $location['slug']) }}">&larr; {{ $location['name'] }}</a></p>
        <h1>Book a table</h1>
        <p class="lead-left">Choose your party size and date to see available times. Your booking is a request: the restaurant confirms it by email.</p>

        <form method="GET" action="{{ route('bookings.create', $location['slug']) }}" class="form-card">
            <div class="form-row">
                <label>Guests
                    <select name="party">
                        @foreach (range(1, $maxParty) as $n)
                            <option value="{{ $n }}" @selected($n === $party)>{{ $n }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Date
                    <select name="date" required>
                        @unless ($date)
                            <option value="" selected disabled>Choose a date</option>
                        @endunless
                        @foreach ($dates as $value => $label)
                            <option value="{{ $value }}" @selected($value === $date)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="btn">Show available times</button>
            </div>
            <p class="muted small">For parties larger than {{ $maxParty }}, please call {{ $location['phone'] }}.</p>
        </form>

        @if ($slots !== null)
            @if (count($slots) === 0)
                <p class="notice">Sorry, no tables are available for {{ $party }} {{ \Illuminate\Support\Str::plural('guest', $party) }} on {{ \Illuminate\Support\Carbon::parse($date)->format(config('site.date_long_format')) }}. Please try another date.</p>
            @else
                <form method="POST" action="{{ route('bookings.store', $location['slug']) }}" class="form-card">
                    @csrf
                    <input type="hidden" name="party_size" value="{{ $party }}">
                    <input type="hidden" name="date" value="{{ $date }}">

                    <h2 class="h-sm">Available times — {{ \Illuminate\Support\Carbon::parse($date)->format(config('site.date_long_format')) }}, {{ $party }} {{ \Illuminate\Support\Str::plural('guest', $party) }}</h2>
                    <div class="slots" role="radiogroup" aria-label="Time">
                        @foreach ($slots as $slot)
                            <label class="slot"><input type="radio" name="time" value="{{ $slot }}" @checked(old('time') === $slot) required><span>{{ $slot }}</span></label>
                        @endforeach
                    </div>
                    @error('time') <p class="error">{{ $message }}</p> @enderror

                    <h2 class="h-sm">Your details</h2>
                    <div class="form-grid">
                        <label>Name <input type="text" name="name" value="{{ old('name') }}" maxlength="120" required autocomplete="name"></label>
                        <label>Phone <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="40" required autocomplete="tel"></label>
                        <label class="wide">Email <input type="email" name="email" value="{{ old('email') }}" maxlength="190" required autocomplete="email"></label>
                        <label class="wide">Notes (allergies, celebrations, high chairs…)
                            <textarea name="notes" rows="3" maxlength="500">{{ old('notes') }}</textarea>
                        </label>
                    </div>
                    @foreach (['name', 'phone', 'email', 'notes', 'party_size', 'date'] as $field)
                        @error($field) <p class="error">{{ $message }}</p> @enderror
                    @endforeach

                    {{-- Honeypot: people never see this, bots fill it in. --}}
                    <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                    <button type="submit" class="btn">Request booking</button>
                </form>
            @endif
        @endif
    </section>
</x-layouts.app>
