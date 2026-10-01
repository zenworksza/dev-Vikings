<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingAvailability;
use App\Services\BookingService;
use App\Services\PortalClient;
use App\Services\PortalUnavailable;
use App\Services\SlotUnavailable;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private BookingAvailability $availability) {}

    public function create(Request $request, PortalClient $portal, string $slug)
    {
        try {
            $location = $portal->location($slug);
        } catch (PortalUnavailable) {
            return view('locations.unavailable');
        }

        abort_if($location === null, 404);

        if (! $this->availability->isBookable($location)) {
            return view('bookings.unavailable', ['location' => $location]);
        }

        $max = $this->availability->maxPartySize($location);
        $party = min(max((int) $request->query('party', 2), 1), $max);
        $date = $this->queryDate($request->query('date'));

        return view('bookings.create', [
            'location' => $location,
            'maxParty' => $max,
            'party' => $party,
            'date' => $date?->toDateString(),
            'slots' => $date ? $this->availability->slots($location, $date, $party) : null,
            'earliest' => today()->toDateString(),
            'latest' => today()->addDays((int) config('site.booking.max_days_ahead'))->toDateString(),
        ]);
    }

    public function store(Request $request, PortalClient $portal, BookingService $bookings, string $slug): RedirectResponse
    {
        try {
            $location = $portal->location($slug);
        } catch (PortalUnavailable) {
            return back()->withInput()->withErrors(['time' => 'We could not reach the booking system. Please try again shortly.']);
        }

        abort_if($location === null || ! $this->availability->isBookable($location), 404);

        $data = $request->validate([
            // Honeypot: hidden from people, filled in by bots.
            'website' => ['prohibited'],
            'party_size' => ['required', 'integer', 'min:1', 'max:'.$this->availability->maxPartySize($location)],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'name' => ['required', 'string', 'max:120', 'not_regex:/[\r\n]/'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:40', 'not_regex:/[\r\n]/'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $booking = $bookings->request($location, $data);
        } catch (SlotUnavailable $e) {
            return back()->withInput()->withErrors(['time' => $e->getMessage().' Please choose another time.']);
        }

        return redirect()->route('bookings.show', $booking)->with('submitted', true);
    }

    public function show(Booking $booking)
    {
        return view('bookings.show', ['booking' => $booking]);
    }

    public function cancel(Booking $booking, BookingService $bookings): RedirectResponse
    {
        $bookings->cancel($booking);

        return redirect()->route('bookings.show', $booking);
    }

    /** A usable booking date from the query string, or null. */
    private function queryDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        $date = CarbonImmutable::createFromFormat('Y-m-d', $value);

        return $date !== false && $this->availability->dateWithinWindow($date) ? $date->startOfDay() : null;
    }
}
