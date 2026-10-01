<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** To the location's inbox: a new request, with a signed link to confirm or decline it. */
class BookingRequestedForStaff extends Mailable
{
    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        $b = $this->booking;

        return new Envelope(
            replyTo: [$b->customer_email],
            subject: "New booking request: {$b->party_size} on {$b->starts_at->format('D d-m-Y \a\t H:i')} ({$b->customer_name})",
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.booking-staff', with: [
            'reviewUrl' => StaffLinks::review($this->booking),
        ]);
    }
}
