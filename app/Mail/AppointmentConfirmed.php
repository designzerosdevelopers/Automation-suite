<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;
    public bool $isOwner;

    public function __construct(Booking $booking, bool $isOwner = false)
    {
        $this->booking = $booking;
        $this->isOwner = $isOwner;
    }

    public function envelope(): Envelope
    {
        $subject = $this->isOwner
            ? '📅 New Appointment Booked!'
            : '✅ Your Appointment is Confirmed!';

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-confirmed',
            with: [
                'booking' => $this->booking,
                'isOwner' => $this->isOwner,
                'clinic' => config('ai-receptionist.clinic'),
            ],
        );
    }
}