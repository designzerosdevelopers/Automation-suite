<?php

namespace App\Services\Notifications;

use App\Contracts\Services\NotificationServiceInterface;
use App\Models\Booking;
use App\Models\Reminder;
use App\Mail\AppointmentConfirmed;
use App\Mail\AppointmentReminder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class NotificationService implements NotificationServiceInterface
{
    protected ?Client $twilio = null;

    public function __construct()
    {
        $sid = config('ai-receptionist.twilio.sid');
        $token = config('ai-receptionist.twilio.token');

        if ($sid && $token) {
            $this->twilio = new Client($sid, $token);
        }
    }

    public function sendAppointmentConfirmation(Booking $booking): void
    {
        // Send to customer
        $this->sendEmailConfirmation($booking);

        // Send to clinic owner
        $this->sendClinicNotification($booking);
    }

    public function sendAppointmentReminder(Booking $booking): void
    {
        // Send email reminder
        $this->sendEmailReminder($booking);

        // Send SMS reminder
        $this->sendSmsReminder($booking);
    }

    public function sendEmailReminder(Booking $booking): bool
    {
        try {
            $lead = $booking->lead;

            if (!$lead->email) {
                return false;
            }

            Mail::to($lead->email)->send(new AppointmentReminder($booking));

            $this->logReminder($booking, 'email', true);

            return true;
        } catch (\Exception $e) {
            Log::error('Email reminder failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            $this->logReminder($booking, 'email', false, $e->getMessage());
            return false;
        }
    }

    public function sendSmsReminder(Booking $booking): bool
    {
        try {
            $lead = $booking->lead;

            if (!$lead->phone || !$this->twilio) {
                return false;
            }

            $message = $this->getSmsReminderText($booking);

            $this->twilio->messages->create(
                $lead->phone,
                [
                    'from' => config('ai-receptionist.twilio.phone'),
                    'body' => $message,
                ]
            );

            $this->logReminder($booking, 'sms', true);

            return true;
        } catch (\Exception $e) {
            Log::error('SMS reminder failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            $this->logReminder($booking, 'sms', false, $e->getMessage());
            return false;
        }
    }

    public function sendEmailConfirmation(Booking $booking): bool
    {
        try {
            $lead = $booking->lead;

            if (!$lead->email) {
                return false;
            }

            Mail::to($lead->email)->send(new AppointmentConfirmed($booking));

            return true;
        } catch (\Exception $e) {
            Log::error('Email confirmation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendClinicNotification(Booking $booking): void
    {
        try {
            $ownerEmail = config('ai-receptionist.notifications.owner_email');

            if (!$ownerEmail) {
                return;
            }

            Mail::to($ownerEmail)->send(new AppointmentConfirmed($booking, true));
        } catch (\Exception $e) {
            Log::error('Clinic notification failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function getSmsReminderText(Booking $booking): string
    {
        $clinic = config('ai-receptionist.clinic');
        $time = $booking->appointment_time->format('g:i A');

        return "Reminder: Your appointment at {$clinic['name']} is at {$time}. Address: {$clinic['address']}";
    }

    protected function logReminder(Booking $booking, string $channel, bool $delivered, ?string $error = null): void
    {
        Reminder::create([
            'booking_id' => $booking->id,
            'type' => '2h',
            'channel' => $channel,
            'sent_at' => now(),
            'delivered' => $delivered,
            'response' => $error,
        ]);
    }
}