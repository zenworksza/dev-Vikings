<?php

return [

    /*
    | Shared secret the public site (../site) sends as a bearer token when it
    | calls /api/v1. Leave empty to switch the API off: every request is then
    | refused. Generate with `php artisan tinker --execute "echo bin2hex(random_bytes(32));"`.
    */
    'api_token' => env('PORTAL_API_TOKEN'),

];
