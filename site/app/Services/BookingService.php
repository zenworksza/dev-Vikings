<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Mail\BookingCancelledForStaff;
use App\Mail\BookingDecided;
use App\Mail\BookingReceived;
use App\Mail\BookingRequestedForStaff;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class BookingService
{
    public function __construct(private BookingAvailability $availability) {}

    /**
     * Creates a pending booking if the slot is still free. Availability is
     * re-checked under a per-location lock so two simultaneous requests cannot
     * both take the last seats.
     *
     * @param  array{party_size: int, date: string, time: string, name: string, email: string, phone: string, notes?: ?string}  $data
     *
     * @throws SlotUnavailable
     */
    public function request(array $location, array $data): Booking
    {
        $start = CarbonImmutable::parse("{$data['date']} {$data['time']}");

        try {
            $booking = Cache::lock("booking.{$location['slug']}", 10)->block(5, function () use ($location, $data, $start) {
                if (! in_array($start->format('H:i'), $this->availability->slots($location, $start, (int) $data['party_size']), true)) {
                    throw new SlotUnavailable('That time is no longer available.');
                }

                return Booking::create([
                    'location_slug' => $location['slug'],
                    'location_name' => $location['name'],
                    'location_email' => $location['booking_email'],
                    'customer_name' => $data['name'],
                    'customer_email' => $data['email'],
                    'customer_phone' => $data['phone'],
                    'party_size' => $data['party_size'],
                    'notes' => $data['notes'] ?? null,
                    'starts_at' => $start,
                    'ends_at' => $start->addMinutes((int) config('site.booking.duration_minutes')),
                ]);
            });
        } catch (LockTimeoutException) {
            throw new SlotUnavailable('We are busy right now. Please try again.');
        }

        $this->send($booking->location_email, new BookingRequestedForStaff($booking));
        $this->send($booking->customer_email, new BookingReceived($booking));

        return $booking;
    }

    /** Staff answer a pending request. Returns false if it was already decided or has passed. */
    public function decide(Booking $booking, BookingStatus $status, ?string $reason = null): bool
    {
        if (! $booking->isPending() || $booking->starts_at->isPast()) {
            return false;
        }

        $booking->update([
            'status' => $status,
            'decided_at' => now(),
            'decline_reason' => $status === BookingStatus::Declined ? $reason : null,
        ]);

        $this->send($booking->customer_email, new BookingDecided($booking));

        return true;
    }

    /** The customer cancels; the restaurant is told so it can free the table. */
    public function cancel(Booking $booking): bool
    {
        if (! $booking->isOpen()) {
            return false;
        }

        $booking->update(['status' => BookingStatus::Cancelled, 'decided_at' => now()]);

        $this->send($booking->location_email, new BookingCancelledForStaff($booking));

        return true;
    }

    /** A mail failure must never lose or fail a booking that has been saved. */
    private function send(string $to, Mailable $mailable): void
    {
        try {
            Mail::to($to)->send($mailable);
        } catch (Throwable $e) {
            Log::error('Booking email failed', ['to' => $to, 'mailable' => $mailable::class, 'error' => $e->getMessage()]);
        }
    }
}
