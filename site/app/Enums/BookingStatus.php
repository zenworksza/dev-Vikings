<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Declined = 'declined';
    case Cancelled = 'cancelled';

    /** Pending requests hold their seats until the restaurant answers. */
    public function holdsSeats(): bool
    {
        return $this === self::Pending || $this === self::Confirmed;
    }

    /** @return list<string> */
    public static function seatHolding(): array
    {
        return [self::Pending->value, self::Confirmed->value];
    }
}
