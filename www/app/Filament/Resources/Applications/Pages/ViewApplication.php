<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Enums\ApplicationStatus;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Models\FranchiseeApplication;
use App\Services\ApplicationWorkflow;
use App\Support\ApplicationSubmission;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewApplication extends ViewRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->workflowAction('startReview', 'Start review', ApplicationStatus::UnderReview)
                ->color('info')
                ->action(fn (ApplicationWorkflow $workflow) => $workflow->startReview($this->application(), auth()->user())),

            $this->workflowAction('requestChanges', 'Request changes', ApplicationStatus::ChangesRequested)
                ->color('warning')
                ->schema([Textarea::make('reason')->label('What needs to change?')->required()])
                ->action(fn (array $data, ApplicationWorkflow $workflow) => $workflow->requestChanges($this->application(), auth()->user(), $data['reason'])),

            $this->workflowAction('approve', 'Approve', ApplicationStatus::Approved)
                ->color('success')
                ->disabled(fn () => ApplicationSubmission::unverifiedDocuments($this->application()) !== [])
                ->tooltip(function () {
                    $unverified = ApplicationSubmission::unverifiedDocuments($this->application());

                    return $unverified === []
                        ? null
                        : 'Accept these documents first: '.collect($unverified)->map(fn ($type) => $type->label())->implode('; ');
                })
                ->requiresConfirmation()
                ->modalDescription('The applicant will be granted franchisee access and notified.')
                ->action(fn (ApplicationWorkflow $workflow) => $workflow->approve($this->application(), auth()->user())),

            $this->workflowAction('reject', 'Reject', ApplicationStatus::Rejected)
                ->color('danger')
                ->schema([Textarea::make('reason')->label('Reason for rejection')->required()])
                ->action(fn (array $data, ApplicationWorkflow $workflow) => $workflow->reject($this->application(), auth()->user(), $data['reason'])),
        ];
    }

    private function application(): FranchiseeApplication
    {
        /** @var FranchiseeApplication */
        return $this->record;
    }

    /** A header action that is only shown when the workflow allows the move. */
    private function workflowAction(string $name, string $label, ApplicationStatus $to): Action
    {
        return Action::make($name)
            ->label($label)
            ->visible(fn () => $this->application()->status->canTransitionTo($to))
            ->after(function () use ($label) {
                $this->refreshFormData(['status', 'reviewed_at', 'decision_reason']);
                Notification::make()->title("{$label} — done")->success()->send();
            });
    }
}
