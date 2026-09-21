<?php

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('platform_admin');
    $this->actingAs($this->admin);
});

test('an admin can create a page with a hero section', function () {
    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Our Story',
            'status' => 'published',
            'sections' => [
                ['type' => 'hero', 'data' => ['headline' => 'Built to last']],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $page = Page::where('slug', 'our-story')->firstOrFail();

    expect($page->sections)->toHaveCount(1)
        ->and($page->sections->first()->type)->toBe('hero')
        ->and($page->sections->first()->data['headline'])->toBe('Built to last');
});

test('editing a page replaces its sections', function () {
    $page = Page::create(['title' => 'Edit Me', 'slug' => 'edit-me', 'status' => 'draft']);
    $page->sections()->create(['type' => 'hero', 'sort_order' => 0, 'data' => ['headline' => 'Old']]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm([
            'sections' => [
                ['type' => 'rich-text', 'data' => ['content' => '<p>New content</p>']],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $page->refresh();

    expect($page->sections)->toHaveCount(1)
        ->and($page->sections->first()->type)->toBe('rich-text');
});
