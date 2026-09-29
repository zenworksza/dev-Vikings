<?php

use App\Enums\ApplicationStatus;
use App\Filament\Resources\Applications\Pages\ListApplications;
use App\Filament\Resources\Applications\Pages\ViewApplication;
use App\Models\FranchiseeApplication;
use App\Models\User;
use App\Services\ApplicationWorkflow;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Notification::fake();

    $this->admin = User::factory()->create()->assignRole('platform_admin');
    $this->actingAs($this->admin);

    $this->applicant = User::factory()->create(['name' => 'Ingrid Olsen']);
    $this->application = FranchiseeApplication::create([
        'user_id' => $this->applicant->id,
        'data' => ['city' => 'Cape Town'],
    ]);
    app(ApplicationWorkflow::class)->submit($this->application, $this->applicant);
});

test('the admin list shows applications', function () {
    Livewire::test(ListApplications::class)
        ->assertSee('Ingrid Olsen')
        ->assertCanSeeTableRecords([$this->application]);
});

test('an admin can review and approve an application', function () {
    Livewire::test(ViewApplication::class, ['record' => $this->application->getKey()])
        ->assertActionVisible('startReview')
        ->assertActionHidden('approve')
        ->callAction('startReview');

    expect($this->application->fresh()->status)->toBe(ApplicationStatus::UnderReview);

    Livewire::test(ViewApplication::class, ['record' => $this->application->getKey()])
        ->assertActionHidden('startReview')
        ->assertActionVisible('approve')
        ->callAction('approve');

    expect($this->application->fresh()->status)->toBe(ApplicationStatus::Approved)
        ->and($this->applicant->fresh()->hasRole('franchisee'))->toBeTrue();
});

test('rejecting needs a reason and stores it', function () {
    app(ApplicationWorkflow::class)->startReview($this->application, $this->admin);

    Livewire::test(ViewApplication::class, ['record' => $this->application->getKey()])
        ->callAction('reject', ['reason' => ''])
        ->assertHasActionErrors(['reason' => 'required']);

    Livewire::test(ViewApplication::class, ['record' => $this->application->getKey()])
        ->callAction('reject', ['reason' => 'Insufficient capital'])
        ->assertHasNoActionErrors();

    expect($this->application->fresh())
        ->status->toBe(ApplicationStatus::Rejected)
        ->decision_reason->toBe('Insufficient capital');
});

test('a non-admin cannot open the admin panel', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin/applications')->assertForbidden();
});
