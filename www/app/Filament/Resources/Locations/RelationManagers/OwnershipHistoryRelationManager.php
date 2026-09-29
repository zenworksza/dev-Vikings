<?php

namespace App\Filament\Resources\Locations\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Read-only audit trail of who owned a location and when. */
class OwnershipHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'ownershipHistory';

    protected static ?string $title = 'Ownership history';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime()->sortable(),
                TextColumn::make('event')->label('Event')->state(fn ($record) => $record->event())->badge(),
                TextColumn::make('change')->label('Owner change')->state(fn ($record) => $record->change()),
                TextColumn::make('actor_name')->label('Done by')->placeholder('System'),
                TextColumn::make('note')->placeholder('—'),
            ])
            ->defaultSort('id', 'desc')
            ->paginated(false);
    }
}
