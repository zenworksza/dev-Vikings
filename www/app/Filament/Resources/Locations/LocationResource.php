<?php

namespace App\Filament\Resources\Locations;

use App\Enums\LocationStatus;
use App\Filament\Resources\Locations\Pages\CreateLocation;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Platform-admin management of every location: create company-owned ones,
 * reassign a location to a franchisee when it is sold, or take one back.
 */
class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $modelLabel = 'location';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components(LocationForm::schema(withOwner: true));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('user.name')->label('Owner')->placeholder('Company-owned')->searchable()->sortable(),
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
                Filter::make('company_owned')
                    ->label('Company-owned only')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereNull('user_id')),
                SelectFilter::make('province')->options(array_combine(LocationForm::PROVINCES, LocationForm::PROVINCES)),
            ])
            ->recordActions([EditAction::make()]);
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
