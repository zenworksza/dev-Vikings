<?php

namespace App\Filament\Resources\Applications\RelationManagers;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\ApplicationDocument;
use App\Services\DocumentReview;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Reviewing an application's documents: open each one (decrypted on
 * download), then accept it or reject it with a reason. Approval is blocked
 * until every required document has an accepted copy.
 */
class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    // Reviewing is done from the read-only "view" page.
    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->formatStateUsing(fn (string $state) => DocumentType::tryFrom($state)?->label() ?? $state)
                    ->wrap(),
                TextColumn::make('original_name')
                    ->label('File')
                    ->url(fn (ApplicationDocument $record) => route('admin.application-documents.download', $record))
                    ->openUrlInNewTab()
                    ->color('primary'),
                TextColumn::make('size')->formatStateUsing(fn ($state) => number_format($state / 1024).' KB'),
                TextColumn::make('created_at')->label('Uploaded')->dateTime(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (DocumentStatus $state) => $state->label())
                    ->color(fn (DocumentStatus $state) => $state->color()),
                TextColumn::make('review_note')->label('Note')->placeholder('—')->wrap(),
                TextColumn::make('reviewer.name')->label('Reviewed by')->placeholder('—'),
            ])
            ->recordActions([
                Action::make('accept')
                    ->label('Accept')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn (ApplicationDocument $record) => $this->reviewable($record) && $record->status !== DocumentStatus::Accepted)
                    ->action(function (ApplicationDocument $record) {
                        app(DocumentReview::class)->accept($record, auth()->user());
                        Notification::make()->success()->title('Document accepted')->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (ApplicationDocument $record) => $this->reviewable($record) && $record->status !== DocumentStatus::Rejected)
                    ->schema([Textarea::make('note')->label('Why is it being rejected?')->required()])
                    ->action(function (ApplicationDocument $record, array $data) {
                        app(DocumentReview::class)->reject($record, auth()->user(), $data['note']);
                        Notification::make()->warning()->title('Document rejected')->send();
                    }),
            ])
            ->emptyStateHeading('No documents uploaded')
            ->paginated(false);
    }

    private function reviewable(ApplicationDocument $document): bool
    {
        return app(DocumentReview::class)->canReview($document);
    }
}
