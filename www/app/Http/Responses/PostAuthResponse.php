<?php

namespace App\Http\Responses;

use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;

/**
 * Where to send someone right after logging in or registering.
 *
 * Fortify's default is "wherever they were headed before" — but an investor
 * who once tried /admin would then land on a 403. So the remembered page is
 * only honoured if this user may actually open it; otherwise /dashboard sends
 * admins to /admin and everyone else to /portal.
 */
class PostAuthResponse implements LoginResponse, RegisterResponse
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $intended = $request->session()->pull('url.intended');
        $path = $intended ? (string) parse_url($intended, PHP_URL_PATH) : '';
        $user = $request->user();

        $allowed = $user && (
            Str::startsWith($path, '/portal')
            || (Str::startsWith($path, '/admin') && $user->hasRole('platform_admin'))
        );

        return redirect($allowed ? $intended : route('dashboard'));
    }
}
