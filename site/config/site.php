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

];
