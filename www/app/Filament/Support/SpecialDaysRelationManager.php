<?php

namespace App\Filament\Support;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Closures and changed hours for specific dates. Leave both times empty for closed all day. */
class SpecialDaysRelationManager extends RelationManager
{
    protected static string $relationship = 'specialDays';

    protected static ?string $title = 'Closures & special hours';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')->required()
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where('location_id', $this->getOwnerRecord()->getKey())),
            TextInput::make('label')->required()->maxLength(120)->placeholder('Christmas Day'),
            TimePicker::make('opens_at')->seconds(false)->requiredWith('closes_at')->helperText('Leave both empty if closed all day.'),
            TimePicker::make('closes_at')->seconds(false)->requiredWith('opens_at')->after('opens_at'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('date')
            ->columns([
                TextColumn::make('date')->date('D d-m-Y')->sortable(),
                TextColumn::make('label'),
                TextColumn::make('hours')->state(fn ($record) => $record->isClosed()
                    ? 'Closed'
                    : substr($record->opens_at, 0, 5).' – '.substr($record->closes_at, 0, 5)),
            ])
            ->headerActions([CreateAction::make()->label('Add date')])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->emptyStateHeading('No special days');
    }
}
