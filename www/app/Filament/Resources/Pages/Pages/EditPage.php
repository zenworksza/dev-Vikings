<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Models\PageSection;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    /**
     * Raw Builder field state — 'data' isn't always present (e.g. a block
     * added but never touched), so it's read defensively below.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $pendingSections = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Page $page */
        $page = $this->record;

        // 'sections' isn't a real column — hydrate the Builder field from
        // the actual page_sections rows. See CreatePage's mutateFormDataBeforeCreate.
        $data['sections'] = $page->sections
            ->map(fn (PageSection $section) => [
                'type' => $section->type,
                'data' => $section->data,
            ])
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingSections = array_values($data['sections'] ?? []);
        unset($data['sections']);

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Page $page */
        $page = $this->record;

        // Simplest correct sync for a handful of marketing sections per
        // page: replace wholesale rather than diff — no per-section
        // history/media to preserve yet (see Plan.md's Phase 2 notes).
        $page->sections()->delete();

        foreach ($this->pendingSections as $index => $section) {
            $page->sections()->create([
                'type' => $section['type'],
                'data' => $section['data'] ?? [],
                'sort_order' => $index,
            ]);
        }
    }
}
