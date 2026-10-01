<?php

namespace App\Http\Resources;

use App\Models\Location;
use App\Models\LocationBusinessHour;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What the public site (never browsers) may know about a location. Leaves out
 * the owner. `booking_email` is for the site's server to send booking requests
 * to; the site must not display it. Add fields here only when the site needs them.
 *
 * @mixin Location
 */
class PublicLocationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'address' => [
                'line1' => $this->address_line1,
                'line2' => $this->address_line2,
                'suburb' => $this->suburb,
                'city' => $this->city,
                'province' => $this->province,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
                'full' => $this->fullAddress(),
            ],
            'phone' => $this->phone,
            'booking_email' => $this->contact_email,
            'seating' => [
                'tables' => $this->table_count,
                'seats' => $this->seat_capacity,
            ],
            'hours' => $this->weeklyHours(),
            'special_days' => $this->specialDays->map(fn ($day) => [
                'date' => $day->date->toDateString(),
                'label' => $day->label,
                'closed' => $day->isClosed(),
                'opens_at' => $day->opens_at ? substr($day->opens_at, 0, 5) : null,
                'closes_at' => $day->closes_at ? substr($day->closes_at, 0, 5) : null,
            ])->values(),
        ];
    }

    /**
     * Monday to Sunday; a day with no row is closed.
     *
     * @return list<array{day: int, name: string, closed: bool, opens_at: string|null, closes_at: string|null}>
     */
    private function weeklyHours(): array
    {
        $rows = $this->businessHours->keyBy('day_of_week');

        return collect(LocationBusinessHour::DAYS)->map(function (string $name, int $day) use ($rows) {
            $row = $rows->get($day);

            return [
                'day' => $day,
                'name' => $name,
                'closed' => $row === null,
                'opens_at' => $row ? substr($row->opens_at, 0, 5) : null,
                'closes_at' => $row ? substr($row->closes_at, 0, 5) : null,
            ];
        })->values()->all();
    }
}
