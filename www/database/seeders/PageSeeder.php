<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Support\SiteTheme;
use Illuminate\Database\Seeder;

/**
 * Seeds the homepage (slug 'home') as a real, staff-editable CMS page —
 * see Plan.md's Phase 2. Idempotent (firstOrCreate + only fills in sections
 * if none exist yet), so it's safe to run on every deploy without
 * clobbering content an admin has since edited in Filament.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $home = Page::firstOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'Home',
                'status' => PageStatus::Published,
                'meta_title' => 'Vikings Portal — Own a location, grow the brand',
                'meta_description' => 'Apply to become a Vikings franchisee, manage your locations, and let customers book directly with you — all from one portal.',
            ]
        );

        if ($home->sections()->exists()) {
            return;
        }

        $home->sections()->create([
            'type' => 'hero',
            'sort_order' => 0,
            'data' => [
                'headline' => 'Own a location. Grow the brand.',
                'subtext' => 'Apply to become a franchisee, manage your locations, and let customers book directly with you — all from one portal.',
                'primary_cta_label' => 'Apply as a franchisee',
                'primary_cta_url' => SiteTheme::portalUrl('/register'),
                'secondary_cta_label' => 'Log in',
                'secondary_cta_url' => SiteTheme::portalUrl('/login'),
            ],
        ]);
    }
}
