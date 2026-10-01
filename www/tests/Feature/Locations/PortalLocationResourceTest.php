<?php

use App\Enums\LocationStatus;
use App\Filament\Portal\Resources\Locations\Pages\CreateLocation;
use App\Filament\Portal\Resources\Locations\Pages\EditLocation;
use App\Filament\Portal\Resources\Locations\Pages\ListLocations;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Filament::setCurrentPanel('portal');

    $this->me = User::factory()->create()->assignRole('franchisee');
    $this->actingAs($this->me);
});

function locationFor(User $owner, string $name): Location
{
    return Location::create([
        'user_id' => $owner->id, 'name' => $name, 'address_line1' => '1 Main Road', 'city' => 'Durban',
        'province' => 'KwaZulu-Natal', 'phone' => '0310000000', 'contact_email' => 'x@example.com', 'table_count' => 10, 'seat_capacity' => 40,
    ]);
}

test('a franchisee can add a location and becomes its owner', function () {
    Livewire::test(CreateLocation::class)
        ->fillForm([
            'name' => 'Vikings Umhlanga',
            'address_line1' => '5 Beach Road',
            'city' => 'Durban',
            'province' => 'KwaZulu-Natal',
            'phone' => '0311234567',
            'contact_email' => 'umhlanga@example.com',
            'table_count' => 10,
            'seat_capacity' => 40,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $location = Location::where('name', 'Vikings Umhlanga')->firstOrFail();

    expect($location->user_id)->toBe($this->me->id)
        ->and($location->status)->toBe(LocationStatus::Active)
        ->and($location->slug)->toBe('vikings-umhlanga-durban')
        ->and($location->table_count)->toBe(10)
        ->and($location->seat_capacity)->toBe(40);
});

test('seating is required and there must be at least one seat per table', function () {
    Livewire::test(CreateLocation::class)
        ->fillForm(['table_count' => null, 'seat_capacity' => null])
        ->call('create')
        ->assertHasFormErrors(['table_count' => 'required', 'seat_capacity' => 'required']);

    Livewire::test(CreateLocation::class)
        ->fillForm(['table_count' => 10, 'seat_capacity' => 4])
        ->call('create')
        ->assertHasFormErrors(['seat_capacity' => 'gte']);
});

test('required fields and a valid booking email are enforced', function () {
    Livewire::test(CreateLocation::class)
        ->fillForm(['name' => '', 'contact_email' => 'not-an-email'])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required', 'address_line1' => 'required', 'phone' => 'required', 'contact_email' => 'email']);
});

test('the list shows only my own locations', function () {
    $mine = locationFor($this->me, 'Mine');
    $theirs = locationFor(User::factory()->create()->assignRole('franchisee'), 'Theirs');

    Livewire::test(ListLocations::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$theirs]);
});

test('I can edit my location, including deactivating it', function () {
    $location = locationFor($this->me, 'Mine');

    Livewire::test(EditLocation::class, ['record' => $location->getRouteKey()])
        ->fillForm(['status' => 'inactive', 'phone' => '0319999999'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($location->fresh())->status->toBe(LocationStatus::Inactive)->phone->toBe('0319999999');
});

test('I cannot open or edit another franchisee’s location', function () {
    $theirs = locationFor(User::factory()->create()->assignRole('franchisee'), 'Theirs');

    expect(fn () => Livewire::test(EditLocation::class, ['record' => $theirs->getRouteKey()]))
        ->toThrow(ModelNotFoundException::class);
});

test('deleting a location soft-deletes it', function () {
    $location = locationFor($this->me, 'Mine');

    Livewire::test(ListLocations::class)->callAction(
        TestAction::make('delete')->table($location)
    );

    expect(Location::find($location->id))->toBeNull()
        ->and(Location::withTrashed()->find($location->id))->not->toBeNull();
});

test('someone who is not a franchisee cannot open the locations area', function () {
    $this->actingAs(User::factory()->create()); // investor, no role

    $this->get('/portal/locations')->assertForbidden();
});
