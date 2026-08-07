<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('brand.site_name', 'Vikings Portal');
        $this->migrator->add('brand.logo_path', null);
        $this->migrator->add('brand.color_primary', '#1d4ed8');
        $this->migrator->add('brand.color_secondary', '#0f172a');
        $this->migrator->add('brand.color_accent', '#f59e0b');
        $this->migrator->add('brand.font_family', 'Instrument Sans, ui-sans-serif, system-ui, sans-serif');
    }
};
