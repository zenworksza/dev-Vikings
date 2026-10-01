<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** To the customer: we have your request; the restaurant will confirm. */
class BookingReceived extends Mailable
{
    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [$this->booking->location_email],
            subject: "We have your booking request for {$this->booking->location_name}",
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.booking-received', with: [
            'url' => route('bookings.show', $this->booking),
        ]);
    }
}
