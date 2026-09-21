<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Placeholder single-theme fields from Phase 1 — superseded by the
        // public/dashboard split below now that the brand concepts (see
        // Source/brand-test/) have been decided: Ember for the public site
        // and auth screens, Hearthwood for the Filament dashboards.
        $this->migrator->deleteIfExists('brand.logo_path');
        $this->migrator->deleteIfExists('brand.color_primary');
        $this->migrator->deleteIfExists('brand.color_secondary');
        $this->migrator->deleteIfExists('brand.color_accent');
        $this->migrator->deleteIfExists('brand.font_family');

        // Fixed client palette (Source/brand-test/brand.css):
        // navy #1B2A38 · slate #46647A · mist #C9D8DD · bone #F1EDE4
        // grey #8A97A0 · parchment #E9E4D8 · red #9B2226 · steel #4A6670

        // Ember — public marketing site + auth (Source/brand-test/ember.html)
        $this->migrator->add('brand.public_logo_path', '/images/brand/logo-gold.png');
        $this->migrator->add('brand.public_color_surface', '#1B2A38');
        $this->migrator->add('brand.public_color_surface_alt', '#22384A');
        $this->migrator->add('brand.public_color_ink', '#F1EDE4');
        $this->migrator->add('brand.public_color_ink_muted', '#C9D8DD');
        $this->migrator->add('brand.public_color_accent', '#9B2226');
        $this->migrator->add('brand.public_color_rule', '#4A6670');
        $this->migrator->add('brand.public_radius', '2px');
        $this->migrator->add('brand.public_heading_font', "'Cinzel', Georgia, serif");
        $this->migrator->add('brand.public_body_font', "'Inter', -apple-system, 'Segoe UI', sans-serif");

        // Hearthwood — /admin + /portal Filament dashboards
        // (Source/brand-test/hearthwood.html)
        $this->migrator->add('brand.dashboard_logo_path', '/images/brand/logo-black.png');
        $this->migrator->add('brand.dashboard_color_surface', '#F1EDE4');
        $this->migrator->add('brand.dashboard_color_surface_alt', '#E9E4D8');
        $this->migrator->add('brand.dashboard_color_ink', '#1B2A38');
        $this->migrator->add('brand.dashboard_color_ink_muted', '#5D6A71');
        $this->migrator->add('brand.dashboard_color_accent', '#9B2226');
        $this->migrator->add('brand.dashboard_color_rule', '#8A97A0');
        $this->migrator->add('brand.dashboard_radius', '12px');
        $this->migrator->add('brand.dashboard_heading_font', "'Bitter', Georgia, serif");
        $this->migrator->add('brand.dashboard_body_font', "'Nunito Sans', -apple-system, 'Segoe UI', sans-serif");
        $this->migrator->add('brand.dashboard_body_font_family', 'Nunito Sans');
    }
};
