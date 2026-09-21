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
                'meta_title' => 'Vikings — Franchise Opportunities',
                'meta_description' => 'For three generations, Vikings has fed coastal families around one hearth. Apply to open the next one — franchise opportunities available now.',
            ]
        );

        if ($home->sections()->exists()) {
            return;
        }

        $home->sections()->createMany([
            [
                'type' => 'hero',
                'sort_order' => 0,
                'data' => [
                    'eyebrow' => 'Franchise With Vikings',
                    'headline' => 'Bring the hearth to your harbor.',
                    'subtext' => "For three generations, Vikings has fed coastal families around a single wood-fired hearth. We're looking for owners ready to light that fire in their own town — a proven menu, a loyal build, and long tables that never go empty.",
                    'primary_cta_label' => 'Apply as a franchisee',
                    'primary_cta_url' => SiteTheme::portalUrl('/register'),
                    'secondary_cta_label' => 'Log in',
                    'secondary_cta_url' => SiteTheme::portalUrl('/login'),
                ],
            ],
            [
                'type' => 'rich-text',
                'sort_order' => 1,
                'data' => [
                    'content' => '<p>Vikings began as a single wood-fired hearth on the harbor — the kind of fire you cook a whole shoulder of lamb over, slowly, while the tide comes in. Three generations later, that hearth is still the center of the room.</p><p>Every board that leaves our kitchen is built for sharing: smoked, cured or roasted the way coastal families have fed each other for a thousand winters, plated for a Tuesday as much as a celebration. That is the business we are franchising — not just a menu, but a room people keep coming back to.</p>',
                ],
            ],
            [
                'type' => 'call-to-action',
                'sort_order' => 2,
                'data' => [
                    'heading' => 'Ready to open your own hearth?',
                    'subtext' => 'Franchisees get a proven menu, a recognizable brand, and hands-on support from application to opening night.',
                    'button_label' => 'Start your application',
                    'button_url' => SiteTheme::portalUrl('/register'),
                ],
            ],
        ]);
    }
}
