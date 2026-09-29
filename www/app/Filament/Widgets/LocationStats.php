<?php

namespace App\Filament\Widgets;

use App\Enums\LocationStatus;
use App\Filament\Resources\Locations\LocationResource;
use App\Models\Location;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Headline location numbers for the admin dashboard. */
class LocationStats extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Locations';

    protected function getStats(): array
    {
        $link = fn (LocationStatus $status): string => LocationResource::getUrl('index', [
            'filters' => ['status' => ['value' => $status->value]],
        ]);

        return [
            Stat::make('Active locations', Location::active()->count())
                ->description('Visible on the public site')
                ->color('success')
                ->url($link(LocationStatus::Active)),
            Stat::make('Inactive locations', Location::where('status', LocationStatus::Inactive->value)->count())
                ->color('gray')
                ->url($link(LocationStatus::Inactive)),
            Stat::make('Franchisees with a location', Location::query()->distinct()->count('user_id'))
                ->description('Owning at least one')
                ->color('info'),
        ];
    }
}
