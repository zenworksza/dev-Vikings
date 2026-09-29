<?php

namespace App\Filament\Resources\Locations;

use App\Enums\LocationStatus;
use App\Filament\Resources\Locations\Pages\EditLocation;
use App\Filament\Resources\Locations\Pages\ListLocations;
use App\Filament\Support\LocationForm;
use App\Models\Location;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Platform-admin oversight of every franchisee's locations. */
class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $modelLabel = 'location';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $recordTitleAttribute = 'name';

    // Locations are created by franchisees; admins review and correct them.
    public static function canCreate(): bool
    {
        return false;
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
                TextColumn::make('user.name')->label('Franchisee')->searchable()->sortable(),
                TextColumn::make('city')->searchable()->sortable(),
                TextColumn::make('province')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (LocationStatus $state) => $state->label())
                    ->color(fn (LocationStatus $state) => $state->color()),
                TextColumn::make('contact_email')->label('Booking email')->color('gray'),
            ])
            ->filters([
                SelectFilter::make('status')->options(
                    collect(LocationStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])
                ),
                SelectFilter::make('province')->options(array_combine(LocationForm::PROVINCES, LocationForm::PROVINCES)),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocations::route('/'),
            'edit' => EditLocation::route('/{record}/edit'),
        ];
    }
}
