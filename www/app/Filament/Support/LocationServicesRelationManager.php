<?php

namespace App\Filament\Support;

use App\Models\LocationService;
use App\Models\Service;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * What a location offers: catalogue services with the location's own price
 * and duration. Shared by the admin and franchisee panels; access follows the
 * parent location (see LocationPolicy).
 */
class LocationServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'locationServices';

    protected static ?string $title = 'Services';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('service_id')
                ->label('Service')
                ->options(function (?LocationService $record): array {
                    $taken = $this->getOwnerRecord()->locationServices()
                        ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                        ->pluck('service_id');

                    return Service::active()->whereNotIn('id', $taken)->orderBy('sort_order')->pluck('name', 'id')->all();
                })
                ->required()->searchable()->live()
                ->disabled(fn (?LocationService $record) => $record !== null)
                ->dehydrated()
                ->afterStateUpdated(function ($state, $set) {
                    $service = Service::find($state);
                    $set('duration_minutes', $service?->default_duration_minutes);
                    $set('price_cents', $service?->default_price_cents === null ? null : number_format($service->default_price_cents / 100, 2, '.', ''));
                }),
            TextInput::make('duration_minutes')->label('Duration (minutes)')
                ->numeric()->integer()->required()->minValue(5)->maxValue(1440),
            Money::input('price_cents', 'Price (R)'),
            Toggle::make('is_active')->label('Offered at this location')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('service.name')->label('Service'),
                TextColumn::make('duration_minutes')->label('Duration')->suffix(' min'),
                TextColumn::make('price_cents')->label('Price')
                    ->formatStateUsing(fn ($state) => Money::format($state))->placeholder('—'),
                IconColumn::make('is_active')->label('Offered')->boolean(),
            ])
            ->headerActions([CreateAction::make()->label('Add service')])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->emptyStateHeading('No services yet')
            ->emptyStateDescription('Add the services this location offers.');
    }
}
