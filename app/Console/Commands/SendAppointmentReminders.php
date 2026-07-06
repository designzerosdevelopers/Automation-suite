<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repositories\BookingRepository;
use App\Services\Notifications\NotificationService;

class SendAppointmentReminders extends Command
{
    protected $signature = 'reminders:send';
    protected $description = 'Send appointment reminders to customers';

    protected BookingRepository $bookingRepository;
    protected NotificationService $notificationService;

    public function __construct(
        BookingRepository $bookingRepository,
        NotificationService $notificationService
    ) {
        parent::__construct();
        $this->bookingRepository = $bookingRepository;
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $this->info('Checking for pending reminders...');

        $bookings = $this->bookingRepository->getPendingReminders(2);

        if (empty($bookings)) {
            $this->info('No pending reminders found.');
            return 0;
        }

        $this->info("Found " . count($bookings) . " bookings needing reminders.");

        $sent = 0;

        foreach ($bookings as $bookingData) {
            $booking = $this->bookingRepository->findById($bookingData['id']);

            if (!$booking) {
                continue;
            }

            $this->line("Sending reminder for booking #{$booking->id}...");

            try {
                // Send email reminder
                $this->notificationService->sendEmailReminder($booking);

                // Send SMS reminder
                $this->notificationService->sendSmsReminder($booking);

                // Mark as sent
                $this->bookingRepository->markReminderSent($booking);

                $sent++;
                $this->info("✅ Reminder sent for booking #{$booking->id}");
            } catch (\Exception $e) {
                $this->error("❌ Failed for booking #{$booking->id}: " . $e->getMessage());
            }
        }

        $this->info("✅ Sent {$sent} reminders successfully.");

        return 0;
    }
}