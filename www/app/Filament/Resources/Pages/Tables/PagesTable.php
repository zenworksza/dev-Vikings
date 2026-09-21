<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Enums\PageStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->color('gray'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (PageStatus $state) => $state->label())
                    ->color(fn (PageStatus $state) => match ($state) {
                        PageStatus::Published => 'success',
                        PageStatus::Draft => 'gray',
                    }),
                TextColumn::make('updated_at')
                    ->label('Last updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(PageStatus::cases())->mapWithKeys(fn (PageStatus $status) => [$status->value => $status->label()])),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
