<?php

namespace App\Contracts\Repositories;

use App\Models\Booking;
use Carbon\Carbon;

interface BookingRepositoryInterface
{
    public function findById(int $id): ?Booking;
    public function findByEventId(string $eventId): ?Booking;
    public function findByConfirmationCode(string $code): ?Booking;
    public function create(array $data): Booking;
    public function update(Booking $booking, array $data): bool;
    public function delete(Booking $booking): bool;
    public function getUpcoming(): array;
    public function getToday(): array;
    public function getForLead(int $leadId): array;
    public function getForDate(string $date): array;
    public function getPendingReminders(int $hoursBefore = 2): array;
    public function markReminderSent(Booking $booking): void;
    public function getOverlapping(string $dateTime, int $durationMinutes = 30, ?int $excludeId = null): array;
}