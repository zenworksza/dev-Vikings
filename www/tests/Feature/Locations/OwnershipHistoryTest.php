<?php

use App\Models\Location;
use App\Models\User;
use App\Notifications\LocationOwnershipChanged;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Notification::fake();

    $this->admin = User::factory()->create(['name' => 'Admin Alice'])->assignRole('platform_admin');
    $this->bob = User::factory()->create(['name' => 'Bob Franchisee'])->assignRole('franchisee');
    $this->cara = User::factory()->create(['name' => 'Cara Franchisee'])->assignRole('franchisee');
});

function newLocation(?User $owner): Location
{
    return Location::create([
        'user_id' => $owner?->id, 'name' => 'Vikings Test', 'address_line1' => '1 Main Road',
        'city' => 'Durban', 'province' => 'KwaZulu-Natal', 'phone' => '0310000000', 'contact_email' => 'x@example.com',
    ]);
}

test('opening a company-owned location is recorded and nobody is emailed', function () {
    $this->actingAs($this->admin);

    $location = newLocation(null);
    $entry = $location->ownershipHistory()->first();

    expect($entry->is_creation)->toBeTrue()
        ->and($entry->event())->toBe('Opened (company-owned)')
        ->and($entry->to_owner_name)->toBeNull()
        ->and($entry->actor_name)->toBe('Admin Alice');

    Notification::assertNothingSent();
});

test('an admin opening a location for a franchisee records it and emails them', function () {
    $this->actingAs($this->admin);

    $location = newLocation($this->bob);

    expect($location->ownershipHistory()->first()->event())->toBe('Opened for franchisee')
        ->and($location->ownershipHistory()->first()->to_owner_name)->toBe('Bob Franchisee');

    Notification::assertSentTo($this->bob, LocationOwnershipChanged::class, fn ($n) => $n->kind === 'assigned');
});

test('a franchisee adding their own location is recorded but not emailed', function () {
    $this->actingAs($this->bob);

    newLocation($this->bob);

    Notification::assertNothingSent();
});

test('selling a company location to a franchisee records it and emails them', function () {
    $this->actingAs($this->admin);
    $location = newLocation(null);

    $location->update(['user_id' => $this->bob->id]);

    $entry = $location->ownershipHistory()->first();

    expect($entry->event())->toBe('Sold / assigned to franchisee')
        ->and($entry->change())->toBe('Franchisor → Bob Franchisee')
        ->and($entry->actor_name)->toBe('Admin Alice');

    Notification::assertSentTo($this->bob, LocationOwnershipChanged::class, fn ($n) => $n->kind === 'assigned');
});

test('taking a location back records it and tells the franchisee who lost it', function () {
    $this->actingAs($this->admin);
    $location = newLocation($this->bob);
    Notification::fake(); // ignore the creation email

    $location->update(['user_id' => null]);

    expect($location->ownershipHistory()->first()->event())->toBe('Taken back by franchisor')
        ->and($location->ownershipHistory()->first()->change())->toBe('Bob Franchisee → Franchisor');

    Notification::assertSentTo($this->bob, LocationOwnershipChanged::class, fn ($n) => $n->kind === 'removed');
});

test('a transfer between franchisees tells both', function () {
    $this->actingAs($this->admin);
    $location = newLocation($this->bob);
    Notification::fake();

    $location->update(['user_id' => $this->cara->id]);

    expect($location->ownershipHistory()->first()->event())->toBe('Transferred between franchisees');

    Notification::assertSentTo($this->cara, LocationOwnershipChanged::class, fn ($n) => $n->kind === 'assigned');
    Notification::assertSentTo($this->bob, LocationOwnershipChanged::class, fn ($n) => $n->kind === 'removed');
});

test('editing other fields does not add a history entry', function () {
    $this->actingAs($this->admin);
    $location = newLocation($this->bob);

    $location->update(['phone' => '0319999999', 'name' => 'Renamed']);

    expect($location->ownershipHistory()->count())->toBe(1); // just the creation
});

test('history keeps the owner name after the franchisee account is deleted', function () {
    $this->actingAs($this->admin);
    $location = newLocation($this->bob);

    $this->bob->delete();

    $history = $location->fresh()->ownershipHistory()->get();

    expect($location->fresh()->isCompanyOwned())->toBeTrue()
        ->and($history->first()->event())->toBe('Taken back by franchisor')
        ->and($history->first()->from_owner_name)->toBe('Bob Franchisee')
        ->and($history->first()->note)->toBe('Owner account deleted')
        ->and($history->last()->to_owner_name)->toBe('Bob Franchisee'); // creation row survives
});

test('the notification email says what happened and where to go', function () {
    $location = newLocation($this->bob);

    $assigned = (new LocationOwnershipChanged($location, 'assigned'))->toMail($this->bob);
    $removed = (new LocationOwnershipChanged($location, 'removed'))->toMail($this->bob);

    expect($assigned->subject)->toContain('added to your account')
        ->and($assigned->actionUrl)->toEndWith('/portal/locations')
        ->and($removed->subject)->toContain('Location update');
});
