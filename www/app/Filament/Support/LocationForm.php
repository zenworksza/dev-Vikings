<?php

namespace App\Filament\Support;

use App\Enums\LocationStatus;
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

    /** @return list<Section> */
    public static function schema(): array
    {
        return [
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
}
