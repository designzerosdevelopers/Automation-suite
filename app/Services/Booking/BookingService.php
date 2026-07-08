<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Repositories\BookingRepository;
use App\Services\Calendar\GoogleCalendarService;
use App\Services\Notifications\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BookingService
{
    protected BookingRepository $bookingRepository;
    protected GoogleCalendarService $googleCalendarService;
    protected NotificationService $notificationService;

    public function __construct(
        BookingRepository $bookingRepository,
        GoogleCalendarService $googleCalendarService,
        NotificationService $notificationService
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->googleCalendarService = $googleCalendarService;
        $this->notificationService = $notificationService;
    }

    public function validateAppointmentTime(string $appointmentDateTime): array
    {
        $now = Carbon::now();
        $appointmentTime = Carbon::parse($appointmentDateTime);

        $minAdvanceHours = (int) config('ai-receptionist.booking.min_advance_notice_hours', 2);
        $maxAdvanceDays = (int) config('ai-receptionist.booking.max_advance_days', 30);
        $opHourStart = (int) config('ai-receptionist.booking.operating_hours.start', 9);
        $opHourEnd = (int) config('ai-receptionist.booking.operating_hours.end', 20);
        $operatingDays = config('ai-receptionist.booking.operating_days', [1, 2, 3, 4, 5, 6]);
        $duration = (int) config('ai-receptionist.booking.default_duration_minutes', 60);

        if ($appointmentTime->lt($now)) {
            return ['valid' => false, 'message' => 'The appointment time must be in the future.'];
        }

        $dayOfWeek = $appointmentTime->dayOfWeek;
        if (!in_array($dayOfWeek, $operatingDays)) {
            return ['valid' => false, 'message' => 'Appointments are only available on weekdays (Mon-Sat).'];
        }

        if ($now->diffInHours($appointmentTime) < $minAdvanceHours) {
            return ['valid' => false, 'message' => "Appointments must be booked at least {$minAdvanceHours} hours in advance."];
        }

        if ($now->diffInDays($appointmentTime) > $maxAdvanceDays) {
            return ['valid' => false, 'message' => "Appointments cannot be booked more than {$maxAdvanceDays} days in advance."];
        }

        $hour = (int)$appointmentTime->format('H');
        if ($hour < $opHourStart || $hour >= $opHourEnd) {
            return ['valid' => false, 'message' => "Appointments are only available between {$opHourStart}:00 and {$opHourEnd}:00."];
        }

        $appointmentEndTime = $appointmentTime->copy()->addMinutes($duration);
        $closingTime = Carbon::parse($appointmentTime->format('Y-m-d') . ' ' . $opHourEnd . ':00');

        if ($appointmentEndTime->gt($closingTime)) {
            return ['valid' => false, 'message' => "Appointments must end at or before {$opHourEnd}:00."];
        }

        return ['valid' => true, 'message' => 'Valid appointment time.'];
    }

    public function checkSlotAvailability(string $datetime): bool
    {
        $existingBooking = Booking::where('appointment_time', $datetime)
            ->where('status', '!=', Booking::STATUS_CANCELLED)
            ->exists();

        return !$existingBooking;
    }

    public function getAlternativeSlots(string $date): array
    {
        $alternatives = [];

        $opHourStart = (int) config('ai-receptionist.booking.operating_hours.start', 9);
        $opHourEnd = (int) config('ai-receptionist.booking.operating_hours.end', 20);
        $duration = (int) config('ai-receptionist.booking.default_duration_minutes', 60);
        $bufferMinutes = (int) config('ai-receptionist.booking.min_buffer_minutes', 60);
        $minAdvanceHours = (int) config('ai-receptionist.booking.min_advance_notice_hours', 2);
        $maxAdvanceDays = (int) config('ai-receptionist.booking.max_advance_days', 30);
        $operatingDays = config('ai-receptionist.booking.operating_days', [1, 2, 3, 4, 5, 6]);

        $startDate = Carbon::now()->addHours($minAdvanceHours);
        $endDate = Carbon::now()->addDays($maxAdvanceDays);
        $requestedDate = Carbon::parse($date);

        for ($i = 0; $i < $maxAdvanceDays; $i++) {
            $checkDate = $requestedDate->copy()->addDays($i);

            if ($checkDate->gt($endDate)) break;
            if ($checkDate->lt($startDate->copy()->startOfDay())) continue;
            if (!in_array($checkDate->dayOfWeek, $operatingDays)) continue;

            $baseTime = $checkDate->copy()->setHour($opHourStart)->setMinute(0);
            $endTime = $checkDate->copy()->setHour($opHourEnd)->setMinute(0);

            while ($baseTime < $endTime) {
                $appointmentEnd = $baseTime->copy()->addMinutes($duration);
                $closingTime = $checkDate->copy()->setHour($opHourEnd)->setMinute(0);
                
                if ($appointmentEnd->gt($closingTime)) {
                    $baseTime->addMinutes($bufferMinutes);
                    continue;
                }
                
                $datetime = $baseTime->format('Y-m-d H:i');
                
                if ($this->checkSlotAvailability($datetime)) {
                    $alternatives[] = [
                        'date' => $baseTime->format('Y-m-d'),
                        'time' => $baseTime->format('H:i'),
                        'datetime' => $datetime,
                        'formatted' => $baseTime->format('l, F j') . ' at ' . $baseTime->format('g:i A'),
                    ];
                }
                
                $baseTime->addMinutes($bufferMinutes);
            }
        }

        return array_slice($alternatives, 0, 10);
    }

    public function createBooking(array $data): array
    {
        $appointmentDateTime = $data['appointment_date'] . ' ' . $data['appointment_time'];
        $duration = (int) config('ai-receptionist.booking.default_duration_minutes', 60);

        // Check for existing active bookings
        $existingBookings = $this->bookingRepository->getActiveBookingsByPhone($data['phone']);
        
        if ($existingBookings->isNotEmpty()) {
            return [
                'success' => false,
                'message' => 'You already have an active booking. Would you like to update or cancel it instead?',
                'action' => 'existing_booking_found',
                'existing_bookings' => $this->formatBookingsForResponse($existingBookings),
                'can_book_another' => config('ai-receptionist.booking.allow_multiple_bookings', false),
            ];
        }

        // Check daily booking limit
        $bookingCountToday = $this->bookingRepository->countBookingsToday($data['phone']);
        $maxBookingsPerDay = Booking::MAX_BOOKINGS_PER_DAY;
        
        if ($bookingCountToday >= $maxBookingsPerDay) {
            return [
                'success' => false,
                'message' => "You have reached the maximum of {$maxBookingsPerDay} bookings per day. Please try again tomorrow.",
                'action' => 'limit_reached',
            ];
        }

        // Create booking in database
        $bookingData = $this->bookingRepository->create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'appointment_time' => $appointmentDateTime,
            'duration_minutes' => $duration,
            'service' => $data['service'],
            'status' => 'confirmed',
            'notes' => $data['notes'] ?? 'Booked via Vapi AI',
            'google_sync_status' => 'pending',
            'last_booking_at' => now(),
            'booking_count_today' => $bookingCountToday + 1,
        ]);

        Log::info('Booking created in database', [
            'booking_id' => $bookingData->id,
            'email' => $data['email'] ?? null,
            'duration_minutes' => $duration
        ]);

        // Create in Google Calendar
        $googleResult = $this->syncWithGoogleCalendar($bookingData, $data);

        Log::info('Appointment booked', [
            'booking_id' => $bookingData->id,
            'appointment_time' => $appointmentDateTime,
            'service' => $data['service'],
            'google_synced' => $googleResult['synced']
        ]);

        return [
            'success' => true,
            'booking' => $bookingData,
            'google_synced' => $googleResult['synced'],
            'google_event_id' => $bookingData->google_event_id,
            'google_event_link' => $bookingData->google_event_link,
        ];
    }

    public function syncWithGoogleCalendar(Booking $booking, array $data): array
    {
        $synced = false;

        try {
            $result = $this->googleCalendarService->createBooking([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'service' => $data['service'],
                'appointment_time' => $booking->appointment_time,
                'duration_minutes' => $booking->duration_minutes,
                'notes' => $data['notes'] ?? null,
                'booking_id' => $booking->id,
            ]);

            $booking->google_event_id = $result['event_id'];
            $booking->google_event_link = $result['event_link'];
            $booking->google_meet_link = $result['conference_link'] ?? null;
            $booking->google_sync_status = 'synced';
            $booking->google_sync_error = null;
            $booking->save();

            $synced = true;

            Log::info('Google Calendar event created successfully', [
                'booking_id' => $booking->id,
                'google_event_id' => $result['event_id']
            ]);

        } catch (\Exception $e) {
            Log::error('Google Calendar sync failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $booking->google_sync_status = 'failed';
            $booking->google_sync_error = substr($e->getMessage(), 0, 500);
            $booking->save();
        }

        return ['synced' => $synced];
    }

    public function cancelBooking(Booking $booking, string $reason = 'Cancelled by customer'): array
    {
        $bookingId = $booking->id;
        $googleEventId = $booking->google_event_id;
        $googleCancelled = false;

        // Check cancellation limit
        $cancellationsToday = $this->bookingRepository->countCancellationsToday($booking->phone);
        $maxCancellationsPerDay = Booking::MAX_CANCELLATIONS_PER_DAY;

        if ($cancellationsToday >= $maxCancellationsPerDay) {
            // Flag the phone number
            $this->bookingRepository->flagPhone($booking->phone);
            
            return [
                'success' => false,
                'message' => "You have reached the maximum of {$maxCancellationsPerDay} cancellations per day. Please contact support.",
                'action' => 'limit_reached',
                'is_flagged' => true,
            ];
        }

        // Check cooldown period
        $lastCancellation = $this->bookingRepository->getLastCancellation($booking->phone);
        if ($lastCancellation) {
            $cooldownMinutes = Booking::CANCELLATION_COOLDOWN_MINUTES;
            $minutesSinceLastCancellation = $lastCancellation->cancelled_at?->diffInMinutes(now()) ?? 999;
            
            if ($minutesSinceLastCancellation < $cooldownMinutes) {
                return [
                    'success' => false,
                    'message' => "You recently cancelled an appointment. Please wait {$cooldownMinutes} minutes before cancelling again.",
                    'action' => 'cooldown_active',
                    'minutes_remaining' => $cooldownMinutes - $minutesSinceLastCancellation,
                ];
            }
        }

        // Cancel the booking in database
        try {
            $this->bookingRepository->cancel($booking, $reason);
            $this->bookingRepository->incrementCancellationCountToday($booking);
        } catch (\Exception $e) {
            Log::error('Booking cancellation failed', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        // Cancel Google Calendar event
        if (!empty($googleEventId)) {
            try {
                $this->googleCalendarService->cancelBooking($googleEventId);
                $googleCancelled = true;
                
                Log::info('Google Calendar event cancelled successfully', [
                    'booking_id' => $bookingId,
                    'google_event_id' => $googleEventId
                ]);
                
            } catch (\Exception $e) {
                Log::error('Google Calendar event cancellation failed', [
                    'booking_id' => $bookingId,
                    'google_event_id' => $googleEventId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $booking->google_sync_status = 'cancellation_failed';
                $booking->google_sync_error = substr($e->getMessage(), 0, 500);
                $booking->save();
            }
        }

        // Check if phone should be flagged for frequent cancellations
        $cancellationsToday = $this->bookingRepository->countCancellationsToday($booking->phone);
        if ($cancellationsToday >= $maxCancellationsPerDay) {
            $this->bookingRepository->flagPhone($booking->phone);
        }

        return [
            'success' => true,
            'booking_id' => $bookingId,
            'google_cancelled' => $googleCancelled,
            'message' => 'Appointment cancelled successfully!',
        ];
    }

    public function findBookingByPhone(string $phone): ?Booking
    {
        return $this->bookingRepository->findByPhoneWithFormats($phone);
    }

    public function getUpcomingBookings(string $phone): array
    {
        return $this->bookingRepository->getUpcomingByPhone($phone)->toArray();
    }

    public function resyncGoogleBookings(?int $bookingId = null, ?int $days = null): array
    {
        $query = Booking::where('google_sync_status', 'failed')
            ->whereNotNull('google_sync_error');

        if ($bookingId) {
            $query->where('id', $bookingId);
        }

        if ($days) {
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $bookings = $query->limit(50)->get();
        $synced = 0;
        $failed = 0;

        foreach ($bookings as $booking) {
            try {
                $result = $this->googleCalendarService->createBooking([
                    'name' => $booking->name,
                    'phone' => $booking->phone,
                    'email' => $booking->email,
                    'service' => $booking->service,
                    'appointment_time' => $booking->appointment_time,
                    'duration_minutes' => $booking->duration_minutes,
                    'notes' => $booking->notes,
                    'booking_id' => $booking->id,
                ]);

                $booking->google_event_id = $result['event_id'];
                $booking->google_event_link = $result['event_link'];
                $booking->google_meet_link = $result['conference_link'] ?? null;
                $booking->google_sync_status = 'synced';
                $booking->google_sync_error = null;
                $booking->save();

                $synced++;

                Log::info('Google Calendar resync successful', [
                    'booking_id' => $booking->id,
                    'google_event_id' => $result['event_id']
                ]);

            } catch (\Exception $e) {
                $failed++;
                Log::error('Google Calendar resync failed', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'synced' => $synced,
            'failed' => $failed,
            'total' => $bookings->count(),
        ];
    }

    protected function sendBookingNotification(Booking $booking): void
    {
        try {
            $this->notificationService->sendAppointmentConfirmation($booking);
        } catch (\Exception $e) {
            Log::error('Booking notification failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function sendCancellationNotification(Booking $booking): void
    {
        try {
            $this->notificationService->sendAppointmentCancellation($booking);
        } catch (\Exception $e) {
            Log::error('Cancellation notification failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getBookingById(int $id): ?Booking
    {
        return $this->bookingRepository->findById($id);
    }

    public function formatBookingForResponse(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'name' => $booking->name,
            'phone' => $booking->phone,
            'email' => $booking->email,
            'service' => $booking->service,
            'appointment_time' => $booking->appointment_time,
            'duration_minutes' => $booking->duration_minutes,
            'status' => $booking->status,
            'notes' => $booking->notes,
            'google_event_id' => $booking->google_event_id,
            'google_event_link' => $booking->google_event_link,
            'google_meet_link' => $booking->google_meet_link,
            'google_sync_status' => $booking->google_sync_status,
            'created_at' => $booking->created_at,
        ];
    }

    public function formatBookingsForResponse($bookings): array
    {
        if ($bookings instanceof \Illuminate\Database\Eloquent\Collection) {
            return $bookings->map(function($booking) {
                return [
                    'id' => $booking->id,
                    'name' => $booking->name,
                    'service' => $booking->service,
                    'appointment_time' => $booking->appointment_time,
                    'formatted_appointment' => $booking->formatted_appointment,
                    'status' => $booking->status,
                    'google_synced' => !empty($booking->google_event_id),
                ];
            })->toArray();
        }

        return [];
    }

    public function checkExistingBooking(string $phone): array
    {
        $bookings = $this->bookingRepository->getActiveBookingsByPhone($phone);
        
        if ($bookings->isEmpty()) {
            return [
                'has_existing' => false,
                'message' => 'No existing active bookings found.',
            ];
        }

        return [
            'has_existing' => true,
            'count' => $bookings->count(),
            'bookings' => $this->formatBookingsForResponse($bookings),
            'message' => "You have {$bookings->count()} active booking(s).",
            'can_cancel' => true,
            'can_update' => true,
        ];
    }

    public function getBookingLimits(string $phone): array
    {
        $bookingsToday = $this->bookingRepository->countBookingsToday($phone);
        $cancellationsToday = $this->bookingRepository->countCancellationsToday($phone);
        
        return [
            'bookings_today' => $bookingsToday,
            'max_bookings_per_day' => Booking::MAX_BOOKINGS_PER_DAY,
            'cancellations_today' => $cancellationsToday,
            'max_cancellations_per_day' => Booking::MAX_CANCELLATIONS_PER_DAY,
            'bookings_remaining' => max(0, Booking::MAX_BOOKINGS_PER_DAY - $bookingsToday),
            'cancellations_remaining' => max(0, Booking::MAX_CANCELLATIONS_PER_DAY - $cancellationsToday),
            'is_flagged' => $this->bookingRepository->findByPhone($phone)?->is_flagged ?? false,
        ];
    }
}