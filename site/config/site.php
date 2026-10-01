<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Franchise portal
    |--------------------------------------------------------------------------
    |
    | The franchise portal is a separate Laravel app with its own database on
    | its own subdomain. This site only links out to it.
    |
    */

    'name' => env('SITE_NAME', 'Vikings'),

    'portal_url' => rtrim(env('PORTAL_URL', 'https://franchise.powerbear.co.za'), '/'),

    /*
    | Server-to-server access to the portal's /api/v1 (locations, seating,
    | hours). In production this is the internal compose address
    | (http://portal) so the token never leaves the Docker network.
    */
    'portal_api_url' => rtrim(env('PORTAL_API_URL', env('PORTAL_URL', 'https://franchise.powerbear.co.za')), '/'),

    'portal_api_token' => env('PORTAL_API_TOKEN'),

    // How long a fetched location is served without asking the portal again.
    'locations_cache_seconds' => 300,

    /*
    | Table booking rules. A booking holds its seats for `duration_minutes`;
    | pending and confirmed bookings both count against a location's seats.
    */
    'booking' => [
        'duration_minutes' => 90,
        'slot_step_minutes' => 30,
        'max_days_ahead' => 60,
        'min_lead_minutes' => 60,
        // Bigger parties are asked to phone the restaurant.
        'max_party_size' => 12,
    ],

];
