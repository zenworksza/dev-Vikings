<?php

namespace App\Notifications;

use App\Enums\ApplicationStatus;
use App\Models\FranchiseeApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public FranchiseeApplication $application,
        public ApplicationStatus $status,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        // Internal moves (e.g. "under review") aren't worth an email.
        return in_array($this->status, [
            ApplicationStatus::Submitted,
            ApplicationStatus::ChangesRequested,
            ApplicationStatus::Approved,
            ApplicationStatus::Rejected,
        ], true) ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting("Hello {$notifiable->name},");
        $reason = $this->application->decision_reason;

        return match ($this->status) {
            ApplicationStatus::Submitted => $mail
                ->subject('We received your franchise application')
                ->line('Thanks — your application has been submitted and our team will review it shortly.'),
            ApplicationStatus::ChangesRequested => $mail
                ->subject('Changes needed on your franchise application')
                ->line('We need a few changes before we can continue:')
                ->line($reason ?? '')
                ->line('Please update your application and resubmit it.'),
            ApplicationStatus::Approved => $mail
                ->subject('Your franchise application was approved')
                ->line('Congratulations — your application has been approved. You now have franchisee access.'),
            ApplicationStatus::Rejected => $mail
                ->subject('An update on your franchise application')
                ->line('After review, we are unable to proceed with your application.')
                ->line($reason ?? ''),
            default => $mail,
        };
    }
}
