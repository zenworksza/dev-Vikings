<?php

namespace App\Filament\Resources\Applications;

use App\Filament\Resources\Applications\Pages\ListApplications;
use App\Filament\Resources\Applications\Pages\ViewApplication;
use App\Filament\Resources\Applications\Tables\ApplicationsTable;
use App\Models\FranchiseeApplication;
use BackedEnum;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Platform-admin review of franchisee applications. Reading only — status
 * changes go through App\Services\ApplicationWorkflow via the header actions
 * on ViewApplication.
 */
class ApplicationResource extends Resource
{
    protected static ?string $model = FranchiseeApplication::class;

    protected static ?string $modelLabel = 'application';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $recordTitleAttribute = 'id';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Applicant')->columns(3)->schema([
                TextEntry::make('user.name')->label('Name'),
                TextEntry::make('user.email')->label('Email'),
                TextEntry::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color()),
                TextEntry::make('submitted_at')->dateTime()->placeholder('Not submitted'),
                TextEntry::make('reviewed_at')->dateTime()->placeholder('—'),
                TextEntry::make('reviewer.name')->label('Reviewed by')->placeholder('—'),
                TextEntry::make('decision_reason')->label('Reviewer note')->placeholder('—')->columnSpanFull(),
            ]),

            Section::make('Application answers')->schema([
                ViewEntry::make('data')->hiddenLabel()->view('filament.admin.application-answers'),
            ]),

            Section::make('Documents')->schema([
                RepeatableEntry::make('documents')->hiddenLabel()->columns(4)->schema([
                    TextEntry::make('type'),
                    TextEntry::make('original_name')
                        ->label('File')
                        ->url(fn ($record) => route('admin.application-documents.download', $record))
                        ->openUrlInNewTab(),
                    TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state->label())
                        ->color(fn ($state) => $state->color()),
                    TextEntry::make('review_note')->placeholder('—'),
                ])->placeholder('No documents uploaded'),
            ]),

            Section::make('History')->collapsed()->schema([
                RepeatableEntry::make('history')->hiddenLabel()->columns(4)->schema([
                    TextEntry::make('created_at')->dateTime(),
                    TextEntry::make('from_status')->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
                    TextEntry::make('to_status')->formatStateUsing(fn ($state) => $state->label()),
                    TextEntry::make('actor.name')->label('By')->placeholder('—'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return ApplicationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApplications::route('/'),
            'view' => ViewApplication::route('/{record}'),
        ];
    }
}
