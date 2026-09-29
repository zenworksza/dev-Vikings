<?php

namespace App\Filament\Widgets;

use App\Enums\LocationStatus;
use App\Filament\Resources\Locations\LocationResource;
use App\Models\Location;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** The newest locations, for a quick look at what franchisees are adding. */
class LatestLocations extends TableWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Latest locations';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Location::query()->with('user')->latest()->limit(5))
            ->paginated(false)
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('user.name')->label('Franchisee'),
                TextColumn::make('city'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (LocationStatus $state) => $state->label())
                    ->color(fn (LocationStatus $state) => $state->color()),
                TextColumn::make('created_at')->label('Added')->since(),
            ])
            ->recordUrl(fn (Location $record) => LocationResource::getUrl('edit', ['record' => $record]))
            ->emptyStateHeading('No locations yet')
            ->emptyStateDescription('Locations added by franchisees will appear here.');
    }
}
