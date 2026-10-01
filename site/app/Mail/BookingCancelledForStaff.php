<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** To the location's inbox: the customer cancelled, so the table is free again. */
class BookingCancelledForStaff extends Mailable
{
    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        $b = $this->booking;

        return new Envelope(
            subject: "Booking cancelled: {$b->party_size} on {$b->starts_at->format('D j M \a\t H:i')} ({$b->customer_name})",
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.booking-cancelled');
    }
}
