<?php

use App\Filament\Portal\Resources\Locations\Pages\EditLocation;
use App\Filament\Support\BusinessHoursRelationManager;
use App\Filament\Support\SpecialDaysRelationManager;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Filament::setCurrentPanel('portal');
    $this->me = User::factory()->create()->assignRole('franchisee');
    $this->actingAs($this->me);
    $this->location = Location::create([
        'user_id' => $this->me->id, 'name' => 'Alpha', 'address_line1' => '1 Main Road', 'city' => 'Durban',
        'province' => 'KwaZulu-Natal', 'phone' => '0310000000', 'contact_email' => 'x@example.com',
    ]);
});

test('hoursOn uses weekly hours, treats missing days as closed and lets special days override', function () {
    $this->location->businessHours()->create(['day_of_week' => 5, 'opens_at' => '09:00', 'closes_at' => '22:00']);
    $this->location->specialDays()->create(['date' => '2026-12-25', 'label' => 'Christmas']);
    $this->location->specialDays()->create(['date' => '2027-01-01', 'label' => 'New Year', 'opens_at' => '09:00', 'closes_at' => '20:00']);

    expect($this->location->hoursOn(Carbon::parse('2026-10-09')))->toBe(['09:00', '22:00']) // Friday
        ->and($this->location->hoursOn(Carbon::parse('2026-10-10')))->toBeNull() // Saturday, no row
        ->and($this->location->hoursOn(Carbon::parse('2026-12-25')))->toBeNull() // closed special day (a Friday)
        ->and($this->location->hoursOn(Carbon::parse('2027-01-01')))->toBe(['09:00', '20:00']);
});

test('a franchisee sets opening hours once per day, closing after opening', function () {
    $page = ['ownerRecord' => $this->location, 'pageClass' => EditLocation::class];

    Livewire::test(BusinessHoursRelationManager::class, $page)
        ->callAction(TestAction::make('create')->table(), ['day_of_week' => 1, 'opens_at' => '09:00', 'closes_at' => '21:00'])
        ->assertHasNoFormErrors();

    Livewire::test(BusinessHoursRelationManager::class, $page)
        ->callAction(TestAction::make('create')->table(), ['day_of_week' => 2, 'opens_at' => '21:00', 'closes_at' => '09:00'])
        ->assertHasFormErrors(['closes_at']);

    expect($this->location->businessHours()->count())->toBe(1);
});

test('a franchisee adds a closure', function () {
    Livewire::test(SpecialDaysRelationManager::class, ['ownerRecord' => $this->location, 'pageClass' => EditLocation::class])
        ->callAction(TestAction::make('create')->table(), ['date' => '2026-12-25', 'label' => 'Christmas Day'])
        ->assertHasNoFormErrors();

    expect($this->location->specialDays()->first()->isClosed())->toBeTrue();
});
