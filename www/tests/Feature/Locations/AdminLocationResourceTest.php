<?php

use App\Enums\LocationStatus;
use App\Filament\Resources\Locations\LocationResource;
use App\Filament\Resources\Locations\Pages\EditLocation;
use App\Filament\Resources\Locations\Pages\ListLocations;
use App\Filament\Widgets\LatestLocations;
use App\Filament\Widgets\LocationStats;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Filament::setCurrentPanel('admin');

    $this->admin = User::factory()->create()->assignRole('platform_admin');
    $this->actingAs($this->admin);
});

function adminLocation(string $name, ?User $owner = null, array $overrides = []): Location
{
    return Location::create([
        'user_id' => ($owner ?? User::factory()->create()->assignRole('franchisee'))->id,
        'name' => $name, 'address_line1' => '1 Main Road', 'city' => 'Durban', 'province' => 'KwaZulu-Natal',
        'phone' => '0310000000', 'contact_email' => 'x@example.com', ...$overrides,
    ]);
}

test('the admin sees every franchisee’s locations', function () {
    $a = adminLocation('Alpha');
    $b = adminLocation('Beta');

    Livewire::test(ListLocations::class)->assertCanSeeTableRecords([$a, $b]);
});

test('admins do not create locations, but can correct one', function () {
    expect(LocationResource::canCreate())->toBeFalse();

    $location = adminLocation('Alpha');

    Livewire::test(EditLocation::class, ['record' => $location->getRouteKey()])
        ->fillForm(['status' => 'inactive'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($location->fresh()->status)->toBe(LocationStatus::Inactive);
});

test('the location stats count active, inactive and owning franchisees', function () {
    $owner = User::factory()->create()->assignRole('franchisee');
    adminLocation('One', $owner);
    adminLocation('Two', $owner);
    adminLocation('Three', null, ['status' => LocationStatus::Inactive]);

    $component = Livewire::test(LocationStats::class);
    $stats = collect((fn () => $this->getStats())->call($component->instance()))
        ->mapWithKeys(fn ($stat) => [(string) $stat->getLabel() => $stat->getValue()]);

    expect($stats['Active locations'])->toBe(2)
        ->and($stats['Inactive locations'])->toBe(1)
        ->and($stats['Franchisees with a location'])->toBe(2);
});

test('the latest locations list and the widgets are on the dashboard', function () {
    adminLocation('Harbour View');

    Livewire::test(LatestLocations::class)->assertSee('Harbour View');

    $this->get('/admin')->assertOk();
    expect(Filament::getPanel('admin')->getWidgets())
        ->toContain(LocationStats::class)
        ->toContain(LatestLocations::class);
});
