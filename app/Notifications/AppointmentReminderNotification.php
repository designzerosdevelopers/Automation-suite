<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentReminderNotification extends Notification
{
    use Queueable;

    protected Booking $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function via($notifiable): array
    {
        $channels = ['mail'];

        if (config('ai-receptionist.twilio.sid')) {
            $channels[] = 'sms';
        }

        return $channels;
    }

    public function toMail($notifiable)
    {
        $clinic = config('ai-receptionist.clinic');
        $time = $this->booking->appointment_time->format('g:i A');

        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('⏰ Appointment Reminder - 2 Hours')
            ->greeting("Hello {$notifiable->name}!")
            ->line("This is a reminder that you have an appointment at {$clinic['name']} in 2 hours.")
            ->line("**Date:** {$this->booking->appointment_time->format('l, F j, Y')}")
            ->line("**Time:** {$time}")
            ->line("**Service:** {$this->booking->service}")
            ->line("**Address:** {$clinic['address']}")
            ->line("**Phone:** {$clinic['phone']}")
            ->action('View Appointment', route('admin.bookings.show', $this->booking->id))
            ->line('Thank you for choosing us!');
    }

    public function toSms($notifiable)
    {
        $clinic = config('ai-receptionist.clinic');
        $time = $this->booking->appointment_time->format('g:i A');

        return "Reminder: Your appointment at {$clinic['name']} is at {$time}. Address: {$clinic['address']}";
    }
}