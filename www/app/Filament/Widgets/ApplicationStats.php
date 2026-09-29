<?php

namespace App\Filament\Widgets;

use App\Enums\ApplicationStatus;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Models\FranchiseeApplication;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Headline franchise-application numbers for the admin dashboard. */
class ApplicationStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Franchise applications';

    protected function getStats(): array
    {
        $counts = FranchiseeApplication::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $count = fn (ApplicationStatus $status): int => (int) ($counts[$status->value] ?? 0);

        $link = fn (ApplicationStatus $status): string => ApplicationResource::getUrl('index', [
            'filters' => ['status' => ['value' => $status->value]],
        ]);

        return [
            Stat::make('Awaiting review', $count(ApplicationStatus::Submitted))
                ->description('Submitted, not yet picked up')
                ->color('warning')
                ->url($link(ApplicationStatus::Submitted)),
            Stat::make('Under review', $count(ApplicationStatus::UnderReview))
                ->description('Being assessed')
                ->color('info')
                ->url($link(ApplicationStatus::UnderReview)),
            Stat::make('Changes requested', $count(ApplicationStatus::ChangesRequested))
                ->description('Waiting on the applicant')
                ->color('gray')
                ->url($link(ApplicationStatus::ChangesRequested)),
            Stat::make('Approved franchisees', $count(ApplicationStatus::Approved))
                ->description('Granted franchisee access')
                ->color('success')
                ->url($link(ApplicationStatus::Approved)),
            Stat::make('Drafts in progress', $count(ApplicationStatus::Draft))
                ->description('Started, not submitted')
                ->color('gray')
                ->url($link(ApplicationStatus::Draft)),
            Stat::make('Rejected', $count(ApplicationStatus::Rejected))
                ->color('danger')
                ->url($link(ApplicationStatus::Rejected)),
        ];
    }
}
