<?php

namespace App\Http\Controllers;

use App\Services\PortalClient;
use App\Services\PortalUnavailable;
use Illuminate\Contracts\View\View;

class LocationController extends Controller
{
    public function index(PortalClient $portal): View
    {
        try {
            return view('locations.index', ['locations' => $portal->locations()]);
        } catch (PortalUnavailable) {
            return view('locations.unavailable');
        }
    }

    public function show(PortalClient $portal, string $slug): View
    {
        try {
            $location = $portal->location($slug);
        } catch (PortalUnavailable) {
            return view('locations.unavailable');
        }

        abort_if($location === null, 404);

        return view('locations.show', ['location' => $location]);
    }
}
