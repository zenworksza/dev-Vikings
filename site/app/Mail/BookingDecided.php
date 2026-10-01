<?php

namespace App\Mail;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** To the customer: the restaurant confirmed or declined. */
class BookingDecided extends Mailable
{
    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        $confirmed = $this->booking->status === BookingStatus::Confirmed;

        return new Envelope(
            replyTo: [$this->booking->location_email],
            subject: ($confirmed ? 'Booking confirmed' : 'Booking not available')." — {$this->booking->location_name}",
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.booking-decided', with: [
            'confirmed' => $this->booking->status === BookingStatus::Confirmed,
            'url' => route('bookings.show', $this->booking),
        ]);
    }
}
