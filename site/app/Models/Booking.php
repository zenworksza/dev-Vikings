<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A table booking request. `token` is the customer's unguessable handle to
 * view or cancel it; staff act through signed links instead (see
 * StaffBookingController).
 *
 * @property BookingStatus $status
 */
#[Fillable([
    'location_slug', 'location_name', 'location_email',
    'customer_name', 'customer_email', 'customer_phone',
    'party_size', 'notes', 'starts_at', 'ends_at', 'status', 'decided_at', 'decline_reason',
])]
class Booking extends Model
{
    protected $attributes = ['status' => 'pending'];

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            $booking->token ??= Str::random(40);
        });
    }

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    /** Still waiting on, or holding a table for, a future sitting. */
    public function isOpen(): bool
    {
        return $this->status->holdsSeats() && $this->starts_at->isFuture();
    }

    public function isPending(): bool
    {
        return $this->status === BookingStatus::Pending;
    }
}
