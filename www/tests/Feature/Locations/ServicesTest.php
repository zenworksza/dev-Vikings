<?php

use App\Filament\Portal\Resources\Locations\Pages\EditLocation as PortalEditLocation;
use App\Filament\Resources\Services\Pages\ManageServices;
use App\Filament\Resources\Services\ServiceResource;
use App\Filament\Support\LocationServicesRelationManager;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function serviceLocation(User $owner): Location
{
    return Location::create([
        'user_id' => $owner->id, 'name' => 'Alpha', 'address_line1' => '1 Main Road', 'city' => 'Durban',
        'province' => 'KwaZulu-Natal', 'phone' => '0310000000', 'contact_email' => 'x@example.com',
    ]);
}

test('an admin manages the service catalogue, storing prices in cents', function () {
    Filament::setCurrentPanel('admin');
    $this->actingAs(User::factory()->create()->assignRole('platform_admin'));

    Livewire::test(ManageServices::class)
        ->callAction('create', [
            'name' => 'Axe throwing', 'default_duration_minutes' => 45, 'default_price_cents' => '150.50', 'is_active' => true,
        ])
        ->assertHasNoFormErrors();

    expect(Service::first())->name->toBe('Axe throwing')->default_price_cents->toBe(15050);
});

test('franchisees cannot reach the service catalogue', function () {
    Filament::setCurrentPanel('admin');
    $this->actingAs(User::factory()->create()->assignRole('franchisee'));

    expect(ServiceResource::canViewAny())->toBeFalse();
});

test('a franchisee adds a catalogue service to their location once, with its own price', function () {
    Filament::setCurrentPanel('portal');
    $me = User::factory()->create()->assignRole('franchisee');
    $this->actingAs($me);
    $location = serviceLocation($me);
    $service = Service::create(['name' => 'Axe throwing', 'default_duration_minutes' => 45]);

    Livewire::test(LocationServicesRelationManager::class, ['ownerRecord' => $location, 'pageClass' => PortalEditLocation::class])
        ->callAction(TestAction::make('create')->table(), [
            'service_id' => $service->id, 'duration_minutes' => 60, 'price_cents' => '200', 'is_active' => true,
        ])
        ->assertHasNoFormErrors();

    expect($location->locationServices()->first())->price_cents->toBe(20000)->duration_minutes->toBe(60);

    Livewire::test(LocationServicesRelationManager::class, ['ownerRecord' => $location, 'pageClass' => PortalEditLocation::class])
        ->callAction(TestAction::make('create')->table(), [
            'service_id' => $service->id, 'duration_minutes' => 30, 'is_active' => true,
        ])
        ->assertHasFormErrors(['service_id']);

    expect($location->locationServices()->count())->toBe(1);
});
