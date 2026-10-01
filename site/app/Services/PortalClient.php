<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reads location data from the franchise portal's token-protected API.
 *
 * Responses are cached briefly; if the portal fails, the last good copy is
 * served instead, so a portal outage does not take the public pages down.
 */
class PortalClient
{
    /** @return list<array<string, mixed>> */
    public function locations(): array
    {
        return $this->cached('locations', fn () => Http::portal()->get('locations')->throw()->json('data'));
    }

    /**
     * @return array<string, mixed>|null null when the location does not exist (or is inactive)
     */
    public function location(string $slug): ?array
    {
        return $this->cached("location.{$slug}", function () use ($slug) {
            $response = Http::portal()->get('locations/'.rawurlencode($slug));

            return $response->status() === 404 ? false : $response->throw()->json('data');
        }) ?: null;
    }

    /**
     * @param  callable(): mixed  $fetch  returns the data, or false for a confirmed "not found"
     */
    private function cached(string $key, callable $fetch): mixed
    {
        $key = "portal.{$key}";

        return Cache::remember($key, config('site.locations_cache_seconds'), function () use ($key, $fetch) {
            try {
                $value = $fetch();
            } catch (Throwable $e) {
                Log::error('Portal API request failed', ['key' => $key, 'error' => $e->getMessage()]);

                return Cache::get("{$key}.stale") ?? throw new PortalUnavailable('The portal is unavailable.', 0, $e);
            }

            Cache::forever("{$key}.stale", $value);

            return $value;
        });
    }
}
