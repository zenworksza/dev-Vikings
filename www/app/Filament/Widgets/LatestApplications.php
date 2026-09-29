<?php

namespace App\Filament\Widgets;

use App\Enums\ApplicationStatus;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Models\FranchiseeApplication;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** The most recently submitted applications, newest first. */
class LatestApplications extends TableWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Latest applications';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FranchiseeApplication::query()
                    ->with('user')
                    ->where('status', '!=', ApplicationStatus::Draft->value)
                    ->latest('submitted_at')
                    ->limit(5)
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('user.name')->label('Applicant'),
                TextColumn::make('user.email')->label('Email')->color('gray'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ApplicationStatus $state) => $state->label())
                    ->color(fn (ApplicationStatus $state) => $state->color()),
                TextColumn::make('submitted_at')->since()->placeholder('—'),
            ])
            ->recordUrl(fn (FranchiseeApplication $record) => ApplicationResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('No applications yet')
            ->emptyStateDescription('Submitted applications will appear here.');
    }
}
