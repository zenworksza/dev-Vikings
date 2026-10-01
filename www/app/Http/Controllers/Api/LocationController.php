<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicLocationResource;
use App\Models\Location;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Read-only location data for the public site. Only active locations are exposed. */
class LocationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PublicLocationResource::collection(
            Location::active()->with($this->relations())->orderBy('name')->get(),
        );
    }

    public function show(string $slug): PublicLocationResource
    {
        return new PublicLocationResource(
            Location::active()->with($this->relations())->where('slug', $slug)->firstOrFail(),
        );
    }

    /** Special days are limited to today onwards; past closures are noise. */
    private function relations(): array
    {
        return [
            'businessHours',
            'specialDays' => fn ($query) => $query->whereDate('date', '>=', today())->orderBy('date'),
        ];
    }
}
