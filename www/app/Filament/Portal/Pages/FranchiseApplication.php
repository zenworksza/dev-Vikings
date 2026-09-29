<?php

namespace App\Filament\Portal\Pages;

use App\Enums\ApplicationStatus;
use App\Filament\Portal\ApplicationForm;
use App\Models\FranchiseeApplication;
use App\Services\ApplicationWorkflow;
use App\Services\DocumentStorage;
use App\Support\ApplicationSubmission;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

/**
 * The investor's franchise application: a resumable wizard (progress is saved
 * as each step validates) that becomes read-only once submitted, and editable
 * again if the reviewer requests changes.
 *
 * @property Schema $form
 */
class FranchiseApplication extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $title = 'Franchise application';

    protected static string $routePath = '/';

    public static function getRoutePath(Panel $panel): string
    {
        return static::$routePath;
    }

    protected string $view = 'filament.portal.pages.application';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public FranchiseeApplication $application;

    public function mount(): void
    {
        $this->application = FranchiseeApplication::firstOrCreate(['user_id' => auth()->id()]);

        $this->form->fill($this->application->data ?? []);
    }

    public function canEdit(): bool
    {
        return $this->application->status->isEditableByApplicant();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make(ApplicationForm::steps(fn () => $this->saveDraft()))
                    ->persistStepInQueryString()
                    ->submitAction(new HtmlString(Blade::render(
                        '<x-filament::button type="submit">Submit application</x-filament::button>'
                    ))),
            ])
            ->statePath('data');
    }

    /** Saves the answers so far (without validating) and stores any new uploads. */
    public function saveDraft(): void
    {
        if (! $this->canEdit()) {
            return;
        }

        $state = $this->form->getRawState();

        $this->application->update(['data' => Arr::except($state, ['uploads'])]);

        $this->storeUploads($state['uploads'] ?? []);
    }

    public function submit(ApplicationWorkflow $workflow): void
    {
        abort_unless($this->canEdit(), 403);

        // Validates every step, not just the current one.
        $this->form->getState();
        $this->saveDraft();

        $missing = ApplicationSubmission::missingDocuments($this->application->refresh());

        if ($missing !== []) {
            Notification::make()
                ->danger()
                ->title('Some required documents are missing')
                ->body(collect($missing)->map(fn ($type) => '• '.$type->label())->implode("\n"))
                ->persistent()
                ->send();

            return;
        }

        $workflow->submit($this->application, auth()->user());

        Notification::make()->success()->title('Application submitted')->send();

        $this->redirect(static::getUrl());
    }

    public function deleteDocument(int $documentId, DocumentStorage $storage): void
    {
        abort_unless($this->canEdit(), 403);

        $storage->delete($this->application->documents()->findOrFail($documentId));
    }

    /** @param  array<string, array<int, mixed>>  $uploads  document type => uploaded files */
    private function storeUploads(array $uploads): void
    {
        $storage = app(DocumentStorage::class);

        foreach ($uploads as $type => $files) {
            foreach ((array) $files as $file) {
                $storage->store($this->application, $file, $type);
            }
        }

        // The files are stored; clear the pickers so they aren't stored twice.
        data_set($this->data, 'uploads', []);
    }

    public function statusLabel(): string
    {
        return $this->application->status->label();
    }

    public function isChangesRequested(): bool
    {
        return $this->application->status === ApplicationStatus::ChangesRequested;
    }
}
