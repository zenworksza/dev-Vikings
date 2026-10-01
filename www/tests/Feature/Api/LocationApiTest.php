<?php

use App\Enums\LocationStatus;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    config()->set('portal.api_token', 'secret-token');
    $this->owner = User::factory()->create()->assignRole('franchisee');
});

function apiLocation(array $overrides = []): Location
{
    return Location::create([
        'user_id' => test()->owner->id, 'name' => 'Vikings Malmesbury', 'city' => 'Malmesbury',
        'address_line1' => 'Shop 18', 'province' => 'Western Cape', 'phone' => '0645192187',
        'contact_email' => 'private@example.com', 'table_count' => 12, 'seat_capacity' => 48,
        ...$overrides,
    ]);
}

function authed(): array
{
    return ['Authorization' => 'Bearer secret-token'];
}

test('requests without the right token are refused', function () {
    apiLocation();

    $this->getJson('/api/v1/locations')->assertUnauthorized();
    $this->getJson('/api/v1/locations', ['Authorization' => 'Bearer wrong'])->assertUnauthorized();
    $this->getJson('/api/v1/locations/vikings-malmesbury-malmesbury')->assertUnauthorized();
});

test('the API is closed when no token is configured, even for an empty bearer', function () {
    config()->set('portal.api_token', null);

    $this->getJson('/api/v1/locations', ['Authorization' => 'Bearer '])->assertUnauthorized();
    $this->getJson('/api/v1/locations')->assertUnauthorized();
});

test('it lists active locations only, with seating, and no private details', function () {
    apiLocation();
    apiLocation(['name' => 'Closed One', 'status' => LocationStatus::Inactive]);

    $response = $this->getJson('/api/v1/locations', authed())->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.slug', 'vikings-malmesbury-malmesbury')
        ->assertJsonPath('data.0.seating', ['tables' => 12, 'seats' => 48])
        ->assertJsonMissingPath('data.0.contact_email')
        ->assertJsonMissingPath('data.0.user_id');
    expect($response->getContent())->not->toContain('private@example.com');
});

test('a location shows weekly hours Monday to Sunday and upcoming special days only', function () {
    $location = apiLocation();
    $location->businessHours()->create(['day_of_week' => 1, 'opens_at' => '09:00', 'closes_at' => '21:00']);
    $location->specialDays()->create(['date' => now()->subDays(3)->toDateString(), 'label' => 'Past']);
    $location->specialDays()->create(['date' => now()->addDays(10)->toDateString(), 'label' => 'Christmas']);

    $data = $this->getJson("/api/v1/locations/{$location->slug}", authed())->assertOk()->json('data');

    expect(array_column($data['hours'], 'name'))->toBe(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])
        ->and($data['hours'][0])->toMatchArray(['closed' => false, 'opens_at' => '09:00', 'closes_at' => '21:00'])
        ->and($data['hours'][1])->toMatchArray(['closed' => true, 'opens_at' => null])
        ->and($data['special_days'])->toHaveCount(1)
        ->and($data['special_days'][0])->toMatchArray(['label' => 'Christmas', 'closed' => true]);
});

test('inactive and unknown locations are 404', function () {
    $inactive = apiLocation(['status' => LocationStatus::Inactive]);

    $this->getJson("/api/v1/locations/{$inactive->slug}", authed())->assertNotFound();
    $this->getJson('/api/v1/locations/nope', authed())->assertNotFound();
});
