<?php

namespace App\Filament\Resources\Services;

use App\Filament\Resources\Services\Pages\ManageServices;
use App\Filament\Support\Money;
use App\Models\Service;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** The master service catalogue. Locations pick from it and set their own price and duration. */
class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $modelLabel = 'service';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(120),
            Textarea::make('description')->rows(3)->maxLength(1000),
            TextInput::make('default_duration_minutes')
                ->label('Default duration (minutes)')
                ->numeric()->integer()->required()->minValue(5)->maxValue(1440)->default(60),
            Money::input('default_price_cents', 'Default price (R)'),
            TextInput::make('sort_order')->numeric()->integer()->minValue(0)->default(0),
            Toggle::make('is_active')->label('Available to locations')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('default_duration_minutes')->label('Duration')->suffix(' min')->sortable(),
                TextColumn::make('default_price_cents')->label('Default price')
                    ->formatStateUsing(fn ($state) => Money::format($state))->placeholder('—'),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('location_services_count')->counts('locationServices')->label('Locations'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageServices::route('/')];
    }
}
