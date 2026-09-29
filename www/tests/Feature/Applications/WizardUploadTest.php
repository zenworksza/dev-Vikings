<?php

use App\Filament\Portal\Pages\FranchiseApplication as WizardPage;
use App\Models\FranchiseeApplication;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Notification::fake();
    Storage::fake('local');
    Filament::setCurrentPanel('portal');

    $this->investor = User::factory()->create();
    $this->actingAs($this->investor);
    $this->application = FranchiseeApplication::create(['user_id' => $this->investor->id]);
});

test('files chosen on the Documents step are stored, encrypted, when the step is completed', function () {
    Livewire::test(WizardPage::class)
        ->fillForm(['uploads' => [
            'identity' => [UploadedFile::fake()->create('id.pdf', 50, 'application/pdf')],
            'proof_of_funds' => [UploadedFile::fake()->create('funds.pdf', 50, 'application/pdf')],
        ]])
        ->goToWizardStep(6)
        ->goToNextWizardStep()
        ->assertHasNoFormErrors();

    $documents = $this->application->documents()->get();

    expect($documents->pluck('type')->sort()->values()->all())->toBe(['identity', 'proof_of_funds'])
        ->and($documents->every(fn ($d) => $d->encrypted))->toBeTrue();

    foreach ($documents as $document) {
        Storage::disk('local')->assertExists($document->path);
    }
});
