<?php

use App\Enums\ApplicationStatus;
use App\Filament\Widgets\ApplicationStats;
use App\Filament\Widgets\LatestApplications;
use App\Models\FranchiseeApplication;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->actingAs(User::factory()->create()->assignRole('platform_admin'));
});

function applicationWithStatus(ApplicationStatus $status, string $name = 'Someone'): FranchiseeApplication
{
    return FranchiseeApplication::create([
        'user_id' => User::factory()->create(['name' => $name])->id,
        'status' => $status,
        'submitted_at' => $status === ApplicationStatus::Draft ? null : now(),
    ]);
}

test('the admin dashboard renders and has the application widgets registered', function () {
    // Widgets load lazily, so check registration rather than first-response HTML.
    $this->get('/admin')->assertOk();

    expect(Filament::getPanel('admin')->getWidgets())
        ->toContain(ApplicationStats::class)
        ->toContain(LatestApplications::class);
});

test('the stats count applications by status', function () {
    applicationWithStatus(ApplicationStatus::Submitted);
    applicationWithStatus(ApplicationStatus::Submitted);
    applicationWithStatus(ApplicationStatus::UnderReview);
    applicationWithStatus(ApplicationStatus::Approved);
    applicationWithStatus(ApplicationStatus::Draft);

    $component = Livewire::test(ApplicationStats::class);

    $stats = collect((fn () => $this->getStats())->call($component->instance()))
        ->mapWithKeys(fn ($stat) => [(string) $stat->getLabel() => $stat->getValue()]);

    expect($stats['Awaiting review'])->toBe(2)
        ->and($stats['Under review'])->toBe(1)
        ->and($stats['Approved franchisees'])->toBe(1)
        ->and($stats['Drafts in progress'])->toBe(1)
        ->and($stats['Rejected'])->toBe(0);
});

test('the latest applications list shows submitted ones and leaves out drafts', function () {
    applicationWithStatus(ApplicationStatus::Submitted, 'Ingrid Olsen');
    applicationWithStatus(ApplicationStatus::Draft, 'Half Finished');

    Livewire::test(LatestApplications::class)
        ->assertSee('Ingrid Olsen')
        ->assertDontSee('Half Finished');
});
