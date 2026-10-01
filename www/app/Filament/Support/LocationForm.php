<?php

namespace App\Filament\Support;

use App\Enums\LocationStatus;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

/** The location form, shared by the franchisee portal and the admin panel. */
class LocationForm
{
    public const PROVINCES = [
        'Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal', 'Limpopo',
        'Mpumalanga', 'Northern Cape', 'North West', 'Western Cape',
    ];

    /**
     * @param  bool  $withOwner  Admin form: show the owner picker (franchisee or company-owned).
     * @return list<Section>
     */
    public static function schema(bool $withOwner = false): array
    {
        return [
            ...($withOwner ? [self::ownerSection()] : []),
            Section::make('Location')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(120)
                    ->helperText('For example "Vikings Hout Bay".'),
                Select::make('status')
                    ->options(collect(LocationStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->default(LocationStatus::Active->value)
                    ->required()
                    ->helperText('Inactive locations are hidden from the public site.'),
                Textarea::make('description')->rows(3)->columnSpanFull()
                    ->helperText('A short blurb shown on the public location page.'),
            ]),
            Section::make('Seating')->columns(2)->schema([
                TextInput::make('table_count')->label('Number of tables')
                    ->numeric()->integer()->required()->minValue(1)->maxValue(1000),
                TextInput::make('seat_capacity')->label('Total seats (pax)')
                    ->numeric()->integer()->required()->minValue(1)->maxValue(5000)
                    ->gte('table_count')
                    ->helperText('The most guests the restaurant can seat at once.'),
            ]),
            Section::make('Address')->columns(2)->schema([
                TextInput::make('address_line1')->label('Address line 1')->required(),
                TextInput::make('address_line2')->label('Address line 2'),
                TextInput::make('suburb'),
                TextInput::make('city')->required(),
                Select::make('province')->options(array_combine(self::PROVINCES, self::PROVINCES))->required(),
                TextInput::make('postal_code')->maxLength(16),
                TextInput::make('country')->default('South Africa')->required(),
            ]),
            Section::make('Contact')->columns(2)->schema([
                TextInput::make('phone')->tel()->required(),
                TextInput::make('contact_email')->label('Booking enquiries email')->email()->required()
                    ->helperText('Booking requests for this location are sent here.'),
            ]),
        ];
    }

    private static function ownerSection(): Section
    {
        return Section::make('Ownership')->schema([
            Select::make('user_id')
                ->label('Owner')
                ->options(fn () => User::role('franchisee')->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->placeholder('Franchisor (company-owned)')
                ->helperText('Leave empty for a company-owned location. Choose a franchisee when a location is sold to them; clear it to take a location back.'),
        ]);
    }
}
