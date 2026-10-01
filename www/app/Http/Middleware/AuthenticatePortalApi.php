<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Guards /api/v1 with the shared token the public site holds. Fails closed when no token is configured. */
class AuthenticatePortalApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('portal.api_token');
        $given = (string) $request->bearerToken();

        if ($expected === '' || $given === '' || ! hash_equals($expected, $given)) {
            return response()->json(['message' => 'Unauthenticated.'], 401, ['WWW-Authenticate' => 'Bearer']);
        }

        return $next($request);
    }
}
