<?php

namespace App\Contracts\Services;

use App\Models\Lead;
use App\Models\Booking;

interface CalendarServiceInterface
{
    /**
     * Check if a time slot is available
     */
    public function isSlotAvailable(string $dateTime, int $durationMinutes = 30, ?int $excludeBookingId = null): bool;

    /**
     * Get available slots for a date
     */
    public function getAvailableSlots(string $date, int $durationMinutes = 30): array;

    /**
     * Book an appointment
     */
    public function bookAppointment(Lead $lead, string $dateTime, string $service, ?int $durationMinutes = null): Booking;

    /**
     * Cancel a booking
     */
    public function cancelBooking(string $eventId): bool;

    /**
     * Get booking by event ID
     */
    public function getBooking(string $eventId): array;

    /**
     * Get all bookings for a date range
     */
    public function getBookings(string $startDate, string $endDate): array;
}