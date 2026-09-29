<?php

namespace App\Filament\Resources\Applications\Tables;

use App\Enums\ApplicationStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('Applicant')->searchable()->sortable(),
                TextColumn::make('user.email')->label('Email')->searchable()->color('gray'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ApplicationStatus $state) => $state->label())
                    ->color(fn (ApplicationStatus $state) => $state->color())
                    ->sortable(),
                TextColumn::make('submitted_at')->dateTime()->sortable()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->options(
                    collect(ApplicationStatus::cases())->mapWithKeys(fn (ApplicationStatus $s) => [$s->value => $s->label()])
                ),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
