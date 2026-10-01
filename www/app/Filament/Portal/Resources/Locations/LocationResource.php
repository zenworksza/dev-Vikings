<?php

namespace App\Filament\Portal\Resources\Locations;

use App\Enums\LocationStatus;
use App\Filament\Portal\Resources\Locations\Pages\CreateLocation;
use App\Filament\Portal\Resources\Locations\Pages\EditLocation;
use App\Filament\Portal\Resources\Locations\Pages\ListLocations;
use App\Filament\Support\BusinessHoursRelationManager;
use App\Filament\Support\LocationForm;
use App\Filament\Support\SpecialDaysRelationManager;
use App\Models\Location;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** A franchisee's own locations. Scoped to the signed-in user; see LocationPolicy. */
class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationLabel = 'My locations';

    protected static ?string $modelLabel = 'location';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(LocationForm::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('city')->searchable()->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (LocationStatus $state) => $state->label())
                    ->color(fn (LocationStatus $state) => $state->color()),
                TextColumn::make('contact_email')->label('Booking email')->color('gray'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('No locations yet')
            ->emptyStateDescription('Add your first location to make it available on the public site.');
    }

    public static function getRelations(): array
    {
        return [BusinessHoursRelationManager::class, SpecialDaysRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocations::route('/'),
            'create' => CreateLocation::route('/create'),
            'edit' => EditLocation::route('/{record}/edit'),
        ];
    }
}
