<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Which start times a location can offer a party on a date: its opening hours
 * (special days override the weekly pattern), minus sittings that would not
 * leave enough free seats once overlapping pending/confirmed bookings count.
 *
 * Works on the portal API's location array, not a model: locations live in
 * the portal's database.
 */
class BookingAvailability
{
    /** Largest party bookable online: the room's seats, capped by site config. */
    public function maxPartySize(array $location): int
    {
        return min((int) ($location['seating']['seats'] ?? 0), (int) config('site.booking.max_party_size'));
    }

    /** Needs seating to check against and an inbox to send requests to. */
    public function isBookable(array $location): bool
    {
        return $this->maxPartySize($location) > 0 && filled($location['booking_email'] ?? null);
    }

    public function dateWithinWindow(CarbonInterface $date): bool
    {
        $day = CarbonImmutable::parse($date)->startOfDay();

        return $day->gte(today()) && $day->lte(today()->addDays((int) config('site.booking.max_days_ahead')));
    }

    /** @return array{0: string, 1: string}|null  [opens, closes] as H:i, or null when closed */
    public function hoursOn(array $location, CarbonInterface $date): ?array
    {
        $special = collect($location['special_days'] ?? [])->firstWhere('date', $date->toDateString());

        $row = $special ?? collect($location['hours'] ?? [])->firstWhere('day', $date->dayOfWeek);

        if ($row === null || ($row['closed'] ?? false) || blank($row['opens_at'] ?? null)) {
            return null;
        }

        return [$row['opens_at'], $row['closes_at']];
    }

    /** @return list<string>  Start times as H:i that can seat the party. */
    public function slots(array $location, CarbonInterface $date, int $party): array
    {
        $day = CarbonImmutable::parse($date)->startOfDay();

        if (! $this->isBookable($location) || ! $this->dateWithinWindow($day)
            || $party < 1 || $party > $this->maxPartySize($location)) {
            return [];
        }

        $hours = $this->hoursOn($location, $day);

        if ($hours === null) {
            return [];
        }

        $duration = (int) config('site.booking.duration_minutes');
        $step = (int) config('site.booking.slot_step_minutes');
        $seats = (int) $location['seating']['seats'];
        $earliest = now()->addMinutes((int) config('site.booking.min_lead_minutes'));

        $bookings = Booking::where('location_slug', $location['slug'])
            ->whereIn('status', BookingStatus::seatHolding())
            ->where('starts_at', '<', $day->addDay())
            ->where('ends_at', '>', $day)
            ->get(['starts_at', 'ends_at', 'party_size']);

        $slots = [];
        $start = $day->setTimeFromTimeString($hours[0]);
        $lastStart = $day->setTimeFromTimeString($hours[1])->subMinutes($duration);

        for (; $start->lte($lastStart); $start = $start->addMinutes($step)) {
            if ($start->lt($earliest)) {
                continue;
            }

            $end = $start->addMinutes($duration);
            $taken = $bookings->filter(fn (Booking $b) => $b->starts_at->lt($end) && $b->ends_at->gt($start))->sum('party_size');

            if ($seats - $taken >= $party) {
                $slots[] = $start->format('H:i');
            }
        }

        return $slots;
    }
}
