<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    /**
     * Raw Builder field state — 'data' isn't always present (e.g. a block
     * added but never touched), so it's read defensively below.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $pendingSections = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 'sections' isn't a real column on `pages` — the Builder field is a
        // virtual form field synced to the page_sections table by hand,
        // since Filament's Builder (unlike Repeater) has no ->relationship().
        $this->pendingSections = array_values($data['sections'] ?? []);
        unset($data['sections']);

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Page $page */
        $page = $this->record;

        $this->syncSections($page);
    }

    protected function syncSections(Page $page): void
    {
        foreach ($this->pendingSections as $index => $section) {
            $page->sections()->create([
                'type' => $section['type'],
                'data' => $section['data'] ?? [],
                'sort_order' => $index,
            ]);
        }
    }
}
