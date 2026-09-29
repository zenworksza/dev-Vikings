<?php

namespace App\Notifications;

use App\Models\Location;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells a franchisee a location was assigned to them, or is no longer theirs. */
class LocationOwnershipChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public const ASSIGNED = 'assigned';

    public const REMOVED = 'removed';

    public function __construct(
        public Location $location,
        public string $kind,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting("Hello {$notifiable->name},");

        if ($this->kind === self::ASSIGNED) {
            return $mail
                ->subject("A location was added to your account: {$this->location->name}")
                ->line("{$this->location->name} ({$this->location->fullAddress()}) has been assigned to you.")
                ->line('You can manage it, and its booking enquiries, from your franchisee portal.')
                ->action('Open my locations', url('/portal/locations'));
        }

        return $mail
            ->subject("Location update: {$this->location->name}")
            ->line("{$this->location->name} ({$this->location->fullAddress()}) is no longer managed from your account.")
            ->line('If you have questions about this change, please contact the franchisor.');
    }
}
