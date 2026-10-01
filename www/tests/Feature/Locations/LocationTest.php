<?php

use App\Enums\LocationStatus;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

function franchisee(): User
{
    return User::factory()->create()->assignRole('franchisee');
}

function makeLocation(User $owner, array $overrides = []): Location
{
    return Location::create([
        'user_id' => $owner->id,
        'name' => 'Vikings Hout Bay',
        'address_line1' => '1 Harbour Road',
        'city' => 'Cape Town',
        'province' => 'Western Cape',
        'phone' => '0210000000',
        'contact_email' => 'houtbay@example.com',
        'table_count' => 10,
        'seat_capacity' => 40,
        ...$overrides,
    ]);
}

test('a new location is active and gets a slug from its name and city', function () {
    $location = makeLocation(franchisee());

    expect($location->status)->toBe(LocationStatus::Active)
        ->and($location->country)->toBe('South Africa')
        ->and($location->slug)->toBe('vikings-hout-bay-cape-town');
});

test('slugs are unique and do not change when the location is renamed', function () {
    $owner = franchisee();
    $first = makeLocation($owner);
    $second = makeLocation($owner);

    expect($second->slug)->toBe('vikings-hout-bay-cape-town-2');

    $first->update(['name' => 'Renamed']);

    expect($first->fresh()->slug)->toBe('vikings-hout-bay-cape-town');
});

test('the active scope leaves out inactive locations', function () {
    $owner = franchisee();
    makeLocation($owner, ['name' => 'Open']);
    makeLocation($owner, ['name' => 'Closed', 'status' => LocationStatus::Inactive]);

    expect(Location::active()->pluck('name')->all())->toBe(['Open']);
});

test('the policy lets franchisees manage only their own locations', function () {
    $mine = franchisee();
    $other = franchisee();
    $admin = User::factory()->create()->assignRole('platform_admin');
    $investor = User::factory()->create();
    $location = makeLocation($mine);

    expect($mine->can('update', $location))->toBeTrue()
        ->and($mine->can('delete', $location))->toBeTrue()
        ->and($other->can('view', $location))->toBeFalse()
        ->and($other->can('update', $location))->toBeFalse()
        ->and($investor->can('viewAny', Location::class))->toBeFalse()
        ->and($admin->can('update', $location))->toBeTrue()
        ->and($admin->can('view', $location))->toBeTrue()
        ->and($mine->can('create', Location::class))->toBeTrue()
        ->and($admin->can('create', Location::class))->toBeTrue()
        ->and($investor->can('create', Location::class))->toBeFalse();
});
