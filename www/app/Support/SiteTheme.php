<?php

namespace App\Support;

use Illuminate\Support\Facades\Request;

/**
 * Picks Ember (public marketing site) vs Hearthwood (franchise portal —
 * investor signup/login plus the Filament /admin and /portal panels) based
 * on which subdomain the current request came in on. See config('app.
 * public_host') / config('app.portal_host') and Plan.md's brand palette
 * decision.
 *
 * Both hostnames are null in local dev, so `isPortal()` is always false and
 * everything renders the public theme — unchanged from before this split.
 */
class SiteTheme
{
    public static function isPortal(): bool
    {
        $portalHost = config('app.portal_host');

        return $portalHost && Request::getHost() === $portalHost;
    }

    /**
     * Absolute URL to $path on the *other* site, for cross-domain links
     * (e.g. the marketing site's "Apply as a franchisee" button pointing at
     * the portal). Falls back to a same-app route/path when the other
     * host isn't configured (local dev). Depends on the current request —
     * only usable inside a web request.
     */
    public static function otherSiteUrl(string $path): string
    {
        $host = static::isPortal() ? config('app.public_host') : config('app.portal_host');

        return $host ? 'https://'.$host.$path : $path;
    }

    /**
     * Absolute URL to $path on the franchise portal, unconditionally —
     * doesn't depend on the current request, so it's safe to call from a
     * seeder/console context (e.g. baking a CTA URL into seeded CMS
     * content). Falls back to a same-app path when the portal host isn't
     * configured (local dev).
     */
    public static function portalUrl(string $path): string
    {
        $host = config('app.portal_host');

        return $host ? 'https://'.$host.$path : $path;
    }
}
