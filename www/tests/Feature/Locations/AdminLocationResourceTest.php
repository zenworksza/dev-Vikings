<?php

use App\Enums\LocationStatus;
use App\Filament\Portal\Resources\Locations\Pages\ListLocations as PortalListLocations;
use App\Filament\Resources\Locations\Pages\CreateLocation;
use App\Filament\Resources\Locations\Pages\EditLocation;
use App\Filament\Resources\Locations\Pages\ListLocations;
use App\Filament\Resources\Locations\RelationManagers\OwnershipHistoryRelationManager;
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
        'phone' => '0310000000', 'contact_email' => 'x@example.com', 'table_count' => 10, 'seat_capacity' => 40, ...$overrides,
    ]);
}

test('the admin sees every franchisee’s locations', function () {
    $a = adminLocation('Alpha');
    $b = adminLocation('Beta');

    Livewire::test(ListLocations::class)->assertCanSeeTableRecords([$a, $b]);
});

test('admins can correct a location', function () {
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
        ->and($stats['Franchisees with a location'])->toBe(2)
        ->and($stats['Company-owned'])->toBe(0);
});

test('the latest locations list and the widgets are on the dashboard', function () {
    adminLocation('Harbour View');

    Livewire::test(LatestLocations::class)->assertSee('Harbour View');

    $this->get('/admin')->assertOk();
    expect(Filament::getPanel('admin')->getWidgets())
        ->toContain(LocationStats::class)
        ->toContain(LatestLocations::class);
});

function newLocationForm(array $overrides = []): array
{
    return [
        'name' => 'Vikings Waterfront',
        'address_line1' => '10 Quay Road',
        'city' => 'Cape Town',
        'province' => 'Western Cape',
        'phone' => '0210001111',
        'contact_email' => 'waterfront@example.com',
        'table_count' => 12,
        'seat_capacity' => 48,
        ...$overrides,
    ];
}

test('an admin can open a company-owned location with no franchisee', function () {
    Livewire::test(CreateLocation::class)
        ->fillForm(newLocationForm())
        ->call('create')
        ->assertHasNoFormErrors();

    $location = Location::where('name', 'Vikings Waterfront')->firstOrFail();

    expect($location->user_id)->toBeNull()
        ->and($location->isCompanyOwned())->toBeTrue()
        ->and($location->status)->toBe(LocationStatus::Active)
        ->and($location->slug)->toBe('vikings-waterfront-cape-town');
});

test('an admin can create a location directly for a franchisee', function () {
    $franchisee = User::factory()->create()->assignRole('franchisee');

    Livewire::test(CreateLocation::class)
        ->fillForm(newLocationForm(['user_id' => $franchisee->id]))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Location::firstWhere('name', 'Vikings Waterfront')->user_id)->toBe($franchisee->id);
});

test('the owner picker offers franchisees only', function () {
    $franchisee = User::factory()->create(['name' => 'Ingrid Franchisee'])->assignRole('franchisee');
    User::factory()->create(['name' => 'Plain Investor']);

    Livewire::test(CreateLocation::class)
        ->assertFormFieldExists('user_id', fn ($field) => array_keys($field->getOptions()) === [$franchisee->id]);
});

test('selling a company-owned location to a franchisee makes it appear in their portal', function () {
    $franchisee = User::factory()->create()->assignRole('franchisee');
    $location = adminLocation('Company Cafe', null, ['user_id' => null]);

    // Before the sale the franchisee sees nothing.
    Filament::setCurrentPanel('portal');
    $this->actingAs($franchisee);
    Livewire::test(PortalListLocations::class)->assertCanNotSeeTableRecords([$location]);

    // The admin assigns it.
    Filament::setCurrentPanel('admin');
    $this->actingAs($this->admin);
    Livewire::test(EditLocation::class, ['record' => $location->getRouteKey()])
        ->fillForm(['user_id' => $franchisee->id])
        ->call('save')
        ->assertHasNoFormErrors();

    Filament::setCurrentPanel('portal');
    $this->actingAs($franchisee);
    Livewire::test(PortalListLocations::class)->assertCanSeeTableRecords([$location->fresh()]);
});

test('taking a failing location back removes it from the franchisee and makes it company-owned', function () {
    $franchisee = User::factory()->create()->assignRole('franchisee');
    $location = adminLocation('Failing Cafe', $franchisee);

    Livewire::test(EditLocation::class, ['record' => $location->getRouteKey()])
        ->fillForm(['user_id' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($location->fresh()->isCompanyOwned())->toBeTrue();

    Filament::setCurrentPanel('portal');
    $this->actingAs($franchisee);
    Livewire::test(PortalListLocations::class)->assertCanNotSeeTableRecords([$location]);
    expect($franchisee->can('update', $location->fresh()))->toBeFalse();
});

test('the company-owned filter shows only locations without an owner', function () {
    $company = adminLocation('Company Cafe', null, ['user_id' => null]);
    $franchised = adminLocation('Franchised Cafe');

    Livewire::test(ListLocations::class)
        ->filterTable('company_owned', true)
        ->assertCanSeeTableRecords([$company])
        ->assertCanNotSeeTableRecords([$franchised]);
});

test('deleting a franchisee’s account keeps their location as company-owned', function () {
    $franchisee = User::factory()->create()->assignRole('franchisee');
    $location = adminLocation('Orphan Cafe', $franchisee);

    $franchisee->delete();

    expect($location->fresh())->not->toBeNull()
        ->and($location->fresh()->isCompanyOwned())->toBeTrue();
});

test('the stats count company-owned locations', function () {
    adminLocation('Company Cafe', null, ['user_id' => null]);
    adminLocation('Franchised Cafe');

    $component = Livewire::test(LocationStats::class);
    $stats = collect((fn () => $this->getStats())->call($component->instance()))
        ->mapWithKeys(fn ($stat) => [(string) $stat->getLabel() => $stat->getValue()]);

    expect($stats['Company-owned'])->toBe(1);
});

test('the ownership history shows on the location page', function () {
    $franchisee = User::factory()->create(['name' => 'Bob Franchisee'])->assignRole('franchisee');
    $location = adminLocation('History Cafe', null, ['user_id' => null]);
    $location->update(['user_id' => $franchisee->id]);

    Livewire::test(OwnershipHistoryRelationManager::class, [
        'ownerRecord' => $location, 'pageClass' => EditLocation::class,
    ])
        ->assertCanSeeTableRecords($location->ownershipHistory)
        ->assertSee('Sold / assigned to franchisee')
        ->assertSee('Franchisor → Bob Franchisee');
});
