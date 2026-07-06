<?php

namespace App\Contracts\Services;

use App\Models\Booking;

interface NotificationServiceInterface
{
    /**
     * Send appointment confirmation
     */
    public function sendAppointmentConfirmation(Booking $booking): void;

    /**
     * Send appointment reminder
     */
    public function sendAppointmentReminder(Booking $booking): void;

    /**
     * Send SMS reminder
     */
    public function sendSmsReminder(Booking $booking): bool;

    /**
     * Send email reminder
     */
    public function sendEmailReminder(Booking $booking): bool;

    /**
     * Send clinic notification for new booking
     */
    public function sendClinicNotification(Booking $booking): void;
}