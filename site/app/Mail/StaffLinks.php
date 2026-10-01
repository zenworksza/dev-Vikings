<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Support\Facades\URL;

/**
 * Staff act on bookings through signed links (no login). A link works until
 * the day after the booking, then expires.
 */
class StaffLinks
{
    public static function review(Booking $booking): string
    {
        return self::signed('staff.bookings.show', $booking);
    }

    public static function signed(string $route, Booking $booking): string
    {
        return URL::temporarySignedRoute($route, $booking->starts_at->copy()->addDay(), ['booking' => $booking->id]);
    }
}
