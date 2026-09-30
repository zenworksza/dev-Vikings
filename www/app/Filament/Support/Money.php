<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;

/** Prices are stored as integer cents and edited/shown in rand. */
class Money
{
    public static function input(string $field, string $label): TextInput
    {
        return TextInput::make($field)
            ->label($label)
            ->numeric()->minValue(0)->maxValue(1000000)->step('0.01')->prefix('R')
            ->formatStateUsing(fn ($state) => $state === null ? null : number_format($state / 100, 2, '.', ''))
            ->dehydrateStateUsing(fn ($state) => blank($state) ? null : (int) round((float) $state * 100));
    }

    public static function format(?int $cents): ?string
    {
        return $cents === null ? null : 'R'.number_format($cents / 100, 2);
    }
}
