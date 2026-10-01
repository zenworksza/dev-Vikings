<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Mail\StaffLinks;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Restaurant staff answer booking requests from the signed links in the
 * notification email. The `signed` middleware is the only authentication, so
 * the links are unguessable, expire, and are valid for one booking only.
 * Actions are POSTs so mail scanners that open links cannot change anything.
 */
class StaffBookingController extends Controller
{
    public function show(Booking $booking)
    {
        return view('staff.booking', [
            'booking' => $booking,
            'confirmUrl' => StaffLinks::signed('staff.bookings.confirm', $booking),
            'declineUrl' => StaffLinks::signed('staff.bookings.decline', $booking),
        ]);
    }

    public function confirm(Booking $booking, BookingService $bookings): RedirectResponse
    {
        $bookings->decide($booking, BookingStatus::Confirmed);

        return redirect(StaffLinks::review($booking));
    }

    public function decline(Request $request, Booking $booking, BookingService $bookings): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:300']])['reason'] ?? null;

        $bookings->decide($booking, BookingStatus::Declined, $reason);

        return redirect(StaffLinks::review($booking));
    }
}
