<?php

namespace App\Filament\Support;

use App\Models\LocationBusinessHour;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Weekly opening hours. A weekday with no row is closed. Shared by both panels. */
class BusinessHoursRelationManager extends RelationManager
{
    protected static string $relationship = 'businessHours';

    protected static ?string $title = 'Opening hours';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('day_of_week')
                ->label('Day')
                ->options(function (?LocationBusinessHour $record): array {
                    $taken = $this->getOwnerRecord()->businessHours()
                        ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                        ->pluck('day_of_week')->all();

                    return collect(LocationBusinessHour::DAYS)->except($taken)->all();
                })
                ->required(),
            TimePicker::make('opens_at')->seconds(false)->required(),
            TimePicker::make('closes_at')->seconds(false)->required()->after('opens_at'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->orderByRaw('(day_of_week + 6) % 7'))
            ->columns([
                TextColumn::make('day_of_week')->label('Day')->formatStateUsing(fn ($state) => LocationBusinessHour::DAYS[$state]),
                TextColumn::make('opens_at')->label('Opens')->formatStateUsing(fn ($state) => substr($state, 0, 5)),
                TextColumn::make('closes_at')->label('Closes')->formatStateUsing(fn ($state) => substr($state, 0, 5)),
            ])
            ->headerActions([CreateAction::make()->label('Add day')])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->paginated(false)
            ->emptyStateHeading('No opening hours')
            ->emptyStateDescription('Days without hours are treated as closed, so nothing can be booked.');
    }
}
