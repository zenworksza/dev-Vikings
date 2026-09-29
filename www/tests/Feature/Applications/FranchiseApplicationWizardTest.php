<?php

use App\Enums\ApplicationStatus;
use App\Enums\DocumentType;
use App\Filament\Portal\Pages\FranchiseApplication as WizardPage;
use App\Models\FranchiseeApplication;
use App\Models\User;
use App\Services\DocumentStorage;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/** A complete, valid set of answers (what the wizard saves in `data`). */
function validAnswers(): array
{
    return [
        'entity_type' => 'sole_proprietorship',
        'phone_mobile' => '0821234567',
        'physical_address' => '1 Harbour Road, Cape Town, South Africa',
        'principals' => [[
            'surname' => 'Olsen', 'first_names' => 'Ingrid', 'date_of_birth' => '1985-03-04',
            'id_number' => '8503045800087', 'nationality' => 'South African',
            'phone' => '0821234567', 'physical_address' => '1 Harbour Road, Cape Town',
        ]],
        'net_worth' => 2500000, 'monthly_income' => 85000, 'cash_available' => 900000,
        'bankers' => [['bank' => 'Nedbank']],
        'food_experience' => 'Ten years running a coastal restaurant.',
        'personal_references' => [
            ['name' => 'A Ref', 'relationship' => 'Friend', 'contact_number' => '0110000001'],
            ['name' => 'B Ref', 'relationship' => 'Colleague', 'contact_number' => '0110000002'],
        ],
        'preferred_area' => 'Hout Bay',
        'motivation' => str_repeat('I want to bring the Vikings hearth to my town. ', 3),
        'personal_profile' => 'Hands-on owner who values long tables and loyal regulars.',
        'accept_fees' => true,
        'accept_declaration' => true,
        'signed_name' => 'Ingrid Olsen',
    ];
}

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Notification::fake();
    Storage::fake('local');

    // Livewire tests don't pass through the panel's routing middleware.
    Filament::setCurrentPanel('portal');

    $this->investor = User::factory()->create();
    $this->actingAs($this->investor);
});

function uploadRequiredDocuments(FranchiseeApplication $application): void
{
    foreach ([DocumentType::Identity, DocumentType::ProofOfFunds, DocumentType::AssetsLiabilities] as $type) {
        app(DocumentStorage::class)->store($application, UploadedFile::fake()->create($type->value.'.pdf', 10, 'application/pdf'), $type->value);
    }
}

test('opening the portal creates a draft application for the investor', function () {
    $this->get('/portal')->assertOk();

    expect($this->investor->fresh()->application->status)->toBe(ApplicationStatus::Draft);
});

test('the post-login redirect sends admins to /admin and investors to /portal', function () {
    $this->get('/dashboard')->assertRedirect('/portal');

    $admin = User::factory()->create()->assignRole('platform_admin');
    $this->actingAs($admin)->get('/dashboard')->assertRedirect('/admin');
});

test('progress is saved as each step is completed', function () {
    Livewire::test(WizardPage::class)
        ->fillForm([
            'entity_type' => 'sole_proprietorship',
            'phone_mobile' => '0821234567',
            'physical_address' => '1 Harbour Road, Cape Town',
        ])
        ->goToNextWizardStep()
        ->assertHasNoFormErrors();

    $data = $this->investor->fresh()->application->data;

    expect($data['entity_type'])->toBe('sole_proprietorship')
        ->and($data['phone_mobile'])->toBe('0821234567');
});

test('a step will not advance without its required answers', function () {
    Livewire::test(WizardPage::class)
        ->fillForm(['entity_type' => null])
        ->goToNextWizardStep()
        ->assertHasFormErrors(['entity_type' => 'required']);
});

test('answers are encrypted at rest', function () {
    $application = FranchiseeApplication::create(['user_id' => $this->investor->id, 'data' => validAnswers()]);

    $raw = DB::table('franchisee_applications')->where('id', $application->id)->value('data');

    expect($raw)->not->toContain('8503045800087')->not->toContain('Olsen')
        ->and($application->fresh()->data['principals'][0]['id_number'])->toBe('8503045800087');
});

test('a complete application with its documents can be submitted', function () {
    $application = FranchiseeApplication::create(['user_id' => $this->investor->id, 'data' => validAnswers()]);
    uploadRequiredDocuments($application);

    Livewire::test(WizardPage::class)->call('submit')->assertHasNoErrors();

    expect($application->fresh())
        ->status->toBe(ApplicationStatus::Submitted)
        ->submitted_at->not->toBeNull();
});

test('submitting without the required documents keeps it a draft', function () {
    $application = FranchiseeApplication::create(['user_id' => $this->investor->id, 'data' => validAnswers()]);

    Livewire::test(WizardPage::class)->call('submit');

    expect($application->fresh()->status)->toBe(ApplicationStatus::Draft);
});

test('a company also needs its registration document', function () {
    $application = FranchiseeApplication::create([
        'user_id' => $this->investor->id,
        'data' => [...validAnswers(), 'entity_type' => 'company', 'business_name' => 'Hearth (Pty) Ltd'],
    ]);
    uploadRequiredDocuments($application);

    Livewire::test(WizardPage::class)->call('submit');
    expect($application->fresh()->status)->toBe(ApplicationStatus::Draft);

    app(DocumentStorage::class)->store($application, UploadedFile::fake()->create('ck1.pdf', 10, 'application/pdf'), DocumentType::CompanyRegistration->value);

    Livewire::test(WizardPage::class)->call('submit');
    expect($application->fresh()->status)->toBe(ApplicationStatus::Submitted);
});

test('a submitted application can no longer be edited', function () {
    $application = FranchiseeApplication::create([
        'user_id' => $this->investor->id, 'data' => validAnswers(), 'status' => ApplicationStatus::Submitted,
    ]);
    $document = app(DocumentStorage::class)->store($application, UploadedFile::fake()->create('id.pdf', 10, 'application/pdf'), 'identity');

    Livewire::test(WizardPage::class)
        ->assertSee('being reviewed')
        ->call('deleteDocument', $document->id)
        ->assertForbidden();

    expect($application->documents()->count())->toBe(1);
});

test('an applicant can remove one of their own documents but not someone else’s', function () {
    $application = FranchiseeApplication::create(['user_id' => $this->investor->id]);
    $mine = app(DocumentStorage::class)->store($application, UploadedFile::fake()->create('id.pdf', 10, 'application/pdf'), 'identity');

    $other = FranchiseeApplication::create(['user_id' => User::factory()->create()->id]);
    $theirs = app(DocumentStorage::class)->store($other, UploadedFile::fake()->create('id.pdf', 10, 'application/pdf'), 'identity');

    expect(fn () => Livewire::test(WizardPage::class)->call('deleteDocument', $theirs->id))
        ->toThrow(ModelNotFoundException::class);
    expect($other->documents()->count())->toBe(1);

    Livewire::test(WizardPage::class)->call('deleteDocument', $mine->id);
    expect($application->documents()->count())->toBe(0);
});

test('changes requested reopens the wizard with the reviewer’s note', function () {
    FranchiseeApplication::create([
        'user_id' => $this->investor->id, 'data' => validAnswers(),
        'status' => ApplicationStatus::ChangesRequested, 'decision_reason' => 'Upload a clearer ID',
    ]);

    Livewire::test(WizardPage::class)->assertSee('Upload a clearer ID');
});
