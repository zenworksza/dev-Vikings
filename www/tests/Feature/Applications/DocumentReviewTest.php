<?php

use App\Enums\ApplicationStatus;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Exceptions\DocumentsNotVerified;
use App\Filament\Portal\Pages\FranchiseApplication;
use App\Filament\Resources\Applications\Pages\ViewApplication;
use App\Filament\Resources\Applications\RelationManagers\DocumentsRelationManager;
use App\Models\ApplicationDocument;
use App\Models\FranchiseeApplication;
use App\Models\User;
use App\Services\ApplicationWorkflow;
use App\Services\DocumentReview;
use App\Support\ApplicationSubmission;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Notification::fake();
    Filament::setCurrentPanel('admin');

    $this->admin = User::factory()->create()->assignRole('platform_admin');
    $this->actingAs($this->admin);

    $this->applicant = User::factory()->create();
    $this->application = FranchiseeApplication::create([
        'user_id' => $this->applicant->id,
        'data' => ['entity_type' => 'sole_proprietorship'],
    ]);
    $this->workflow = app(ApplicationWorkflow::class);
    $this->workflow->submit($this->application, $this->applicant);
});

function pendingDocument(FranchiseeApplication $application, DocumentType $type): ApplicationDocument
{
    return $application->documents()->create([
        'type' => $type->value, 'disk' => 'local', 'path' => 'applications/test/'.uniqid().'.enc',
        'original_name' => $type->value.'.pdf', 'size' => 2048,
    ]);
}

function relationManagerFor(FranchiseeApplication $application)
{
    return Livewire::test(DocumentsRelationManager::class, [
        'ownerRecord' => $application, 'pageClass' => ViewApplication::class,
    ]);
}

test('an admin can accept a document, and it records who and when', function () {
    $document = pendingDocument($this->application, DocumentType::Identity);

    relationManagerFor($this->application)->callAction(
        TestAction::make('accept')->table($document)
    );

    expect($document->fresh())
        ->status->toBe(DocumentStatus::Accepted)
        ->reviewed_by->toBe($this->admin->id)
        ->reviewed_at->not->toBeNull();
});

test('rejecting a document needs a reason and stores it', function () {
    $document = pendingDocument($this->application, DocumentType::Identity);
    $action = TestAction::make('reject')->table($document);

    relationManagerFor($this->application)
        ->callAction($action, ['note' => ''])
        ->assertHasActionErrors(['note' => 'required']);

    relationManagerFor($this->application)
        ->callAction($action, ['note' => 'Photo is blurry'])
        ->assertHasNoActionErrors();

    expect($document->fresh())->status->toBe(DocumentStatus::Rejected)->review_note->toBe('Photo is blurry');
});

test('documents can only be reviewed while the application is with the reviewer', function () {
    $document = pendingDocument($this->application, DocumentType::Identity);
    $this->workflow->startReview($this->application, $this->admin);

    expect(app(DocumentReview::class)->canReview($document->fresh()))->toBeTrue();

    acceptRequiredDocuments($this->application);
    $this->workflow->approve($this->application, $this->admin);

    expect(app(DocumentReview::class)->canReview($document->fresh()))->toBeFalse()
        ->and(fn () => app(DocumentReview::class)->accept($document->fresh(), $this->admin))->toThrow(DomainException::class);
});

test('approval is blocked until every required document is accepted', function () {
    $this->workflow->startReview($this->application, $this->admin);
    $identity = pendingDocument($this->application, DocumentType::Identity);
    $funds = pendingDocument($this->application, DocumentType::ProofOfFunds);
    $statement = pendingDocument($this->application, DocumentType::AssetsLiabilities);

    expect(fn () => $this->workflow->approve($this->application, $this->admin))
        ->toThrow(DocumentsNotVerified::class, 'ID documents');

    $review = app(DocumentReview::class);
    $review->accept($identity, $this->admin);
    $review->accept($funds, $this->admin);
    $review->reject($statement, $this->admin, 'Unsigned');

    expect(fn () => $this->workflow->approve($this->application, $this->admin))
        ->toThrow(DocumentsNotVerified::class, 'Statement of assets');
    expect($this->application->fresh()->status)->toBe(ApplicationStatus::UnderReview)
        ->and($this->applicant->fresh()->hasRole('franchisee'))->toBeFalse();

    $review->accept($statement, $this->admin);
    $this->workflow->approve($this->application, $this->admin);

    expect($this->application->fresh()->status)->toBe(ApplicationStatus::Approved)
        ->and($this->applicant->fresh()->hasRole('franchisee'))->toBeTrue();
});

test('the Approve button is disabled with an explanation until documents are accepted', function () {
    $this->workflow->startReview($this->application, $this->admin);

    Livewire::test(ViewApplication::class, ['record' => $this->application->getKey()])
        ->assertActionDisabled('approve');

    acceptRequiredDocuments($this->application);

    Livewire::test(ViewApplication::class, ['record' => $this->application->getKey()])
        ->assertActionEnabled('approve');
});

test('a company also needs its registration accepted', function () {
    $this->application->update(['data' => ['entity_type' => 'company']]);

    expect(collect(ApplicationSubmission::unverifiedDocuments($this->application))->map->value->all())
        ->toContain('company_registration');
});

test('a rejected document no longer counts as uploaded, so the applicant must replace it', function () {
    $this->workflow->startReview($this->application, $this->admin);
    $identity = pendingDocument($this->application, DocumentType::Identity);

    expect(collect(ApplicationSubmission::missingDocuments($this->application))->map->value->all())
        ->not->toContain('identity');

    app(DocumentReview::class)->reject($identity, $this->admin, 'Expired ID');

    expect(collect(ApplicationSubmission::missingDocuments($this->application))->map->value->all())
        ->toContain('identity');
});

test('the applicant sees rejected documents and the reason when changes are requested', function () {
    $this->workflow->startReview($this->application, $this->admin);
    $identity = pendingDocument($this->application, DocumentType::Identity);
    app(DocumentReview::class)->reject($identity, $this->admin, 'Expired ID');
    $this->workflow->requestChanges($this->application, $this->admin, 'Please replace your ID');

    Filament::setCurrentPanel('portal');
    $this->actingAs($this->applicant);

    Livewire::test(FranchiseApplication::class)
        ->assertSee('Documents to replace')
        ->assertSee('Expired ID');
});
