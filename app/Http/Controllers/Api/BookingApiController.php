<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Booking\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class BookingApiController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    public function checkAvailability(Request $request)
    {
        try {
            $validated = $request->validate([
                'date' => 'required|date|after_or_equal:today',
                'time' => 'required|date_format:H:i',
            ]);

            $appointmentDateTime = $validated['date'] . ' ' . $validated['time'];

            $validationResult = $this->bookingService->validateAppointmentTime($appointmentDateTime);
            if (!$validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'available' => false,
                    'message' => $validationResult['message'],
                    'alternatives' => $this->bookingService->getAlternativeSlots($validated['date']),
                ], 400);
            }

            $isAvailable = $this->bookingService->checkSlotAvailability($appointmentDateTime);

            return response()->json([
                'success' => true,
                'available' => $isAvailable,
                'time' => $appointmentDateTime,
                'alternatives' => $isAvailable ? [] : $this->bookingService->getAlternativeSlots($validated['date']),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Check Availability Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking availability.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function bookAppointment(Request $request)
    {
        try {
            Log::info('Booking request received', ['payload' => $request->all()]);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'service' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'nullable|email|max:255',
                'appointment_date' => 'required|date|after_or_equal:today',
                'appointment_time' => 'required|date_format:H:i',
                'notes' => 'nullable|string|max:1000',
            ]);

            // Check for existing bookings
            $existingCheck = $this->bookingService->checkExistingBooking($validated['phone']);
            
            if ($existingCheck['has_existing']) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an existing booking. Would you like to update or cancel it instead?',
                    'action' => 'existing_booking_found',
                    'existing_bookings' => $existingCheck['bookings'],
                    'options' => [
                        'update' => 'Update your existing appointment',
                        'cancel' => 'Cancel your existing appointment',
                        'book_new' => 'Book a new appointment (additional booking)',
                    ],
                ], 409);
            }

            $appointmentDateTime = $validated['appointment_date'] . ' ' . $validated['appointment_time'];

            $validationResult = $this->bookingService->validateAppointmentTime($appointmentDateTime);
            if (!$validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'available' => false,
                    'message' => $validationResult['message'],
                    'alternatives' => $this->bookingService->getAlternativeSlots($validated['appointment_date']),
                ], 400);
            }

            $isAvailable = $this->bookingService->checkSlotAvailability($appointmentDateTime);
            if (!$isAvailable) {
                return response()->json([
                    'success' => false,
                    'available' => false,
                    'message' => 'The requested time slot is not available.',
                    'alternatives' => $this->bookingService->getAlternativeSlots($validated['appointment_date']),
                ], 409);
            }

            $result = $this->bookingService->createBooking($validated);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'action' => $result['action'] ?? null,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'available' => true,
                'message' => 'Appointment booked successfully!',
                'booking' => $this->bookingService->formatBookingForResponse($result['booking']),
                'google_synced' => $result['google_synced'],
                'google_event_link' => $result['google_event_link'] ?? null,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'available' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Vapi Booking Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'available' => false,
                'message' => 'An error occurred while booking the appointment.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function cancelAppointment(Request $request)
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string|max:20',
                'reason' => 'nullable|string|max:500',
            ]);

            $booking = $this->bookingService->findBookingByPhone($validated['phone']);

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'No upcoming booking found for this phone number.',
                ], 404);
            }

            $result = $this->bookingService->cancelBooking($booking, $validated['reason'] ?? 'Cancelled via Vapi AI');

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'action' => $result['action'] ?? null,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'booking_id' => $result['booking_id'],
                'google_cancelled' => $result['google_cancelled'],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', array_map(fn($errors) => implode(', ', $errors), $e->errors())),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Vapi Cancellation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while cancelling the appointment.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function updateAppointment(Request $request)
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string|max:20',
                'booking_id' => 'nullable|integer|exists:bookings,id',
                'new_date' => 'required|date|after_or_equal:today',
                'new_time' => 'required|date_format:H:i',
                'new_service' => 'nullable|string|max:255',
                'new_name' => 'nullable|string|max:255',
                'new_phone' => 'nullable|string|max:20',
                'new_email' => 'nullable|email|max:255',
                'new_notes' => 'nullable|string|max:1000',
            ]);

            // Find the booking
            $booking = null;
            if (isset($validated['booking_id'])) {
                $booking = $this->bookingService->getBookingById($validated['booking_id']);
            } else {
                $booking = $this->bookingService->findBookingByPhone($validated['phone']);
            }

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'No booking found to update.',
                ], 404);
            }

            // Check if booking can be updated
            if (!$booking->canBeRescheduled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This booking cannot be updated. It may be cancelled, completed, or in the past.',
                ], 400);
            }

            $newDateTime = $validated['new_date'] . ' ' . $validated['new_time'];

            // Validate the new time
            $validationResult = $this->bookingService->validateAppointmentTime($newDateTime);
            if (!$validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validationResult['message'],
                    'alternatives' => $this->bookingService->getAlternativeSlots($validated['new_date']),
                ], 400);
            }

            // Check if new slot is available (excluding current booking)
            $existingBooking = Booking::where('appointment_time', $newDateTime)
                ->where('id', '!=', $booking->id)
                ->where('status', '!=', Booking::STATUS_CANCELLED)
                ->exists();

            if ($existingBooking) {
                return response()->json([
                    'success' => false,
                    'message' => 'The requested time slot is not available.',
                    'alternatives' => $this->bookingService->getAlternativeSlots($validated['new_date']),
                ], 409);
            }

            // Prepare update data
            $updateData = [
                'appointment_time' => $newDateTime,
            ];

            if (isset($validated['new_service'])) {
                $updateData['service'] = $validated['new_service'];
            }
            if (isset($validated['new_name'])) {
                $updateData['name'] = $validated['new_name'];
            }
            if (isset($validated['new_phone'])) {
                $updateData['phone'] = $validated['new_phone'];
            }
            if (isset($validated['new_email'])) {
                $updateData['email'] = $validated['new_email'];
            }
            if (isset($validated['new_notes'])) {
                $updateData['notes'] = $validated['new_notes'];
            }

            // Update in database
            $updatedBooking = $this->bookingRepository->update($booking, $updateData);

            // Update Google Calendar if event exists
            $googleUpdated = false;
            if ($booking->google_event_id) {
                try {
                    $this->googleCalendarService->updateBooking(
                        $booking->google_event_id,
                        $updateData
                    );
                    $googleUpdated = true;
                } catch (\Exception $e) {
                    Log::error('Google Calendar update failed', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage()
                    ]);
                    $booking->markGoogleFailed('Update failed: ' . $e->getMessage());
                }
            } else {
                // Try to sync to Google Calendar
                $syncResult = $this->bookingService->syncWithGoogleCalendar($updatedBooking, $updatedBooking->toArray());
                $googleUpdated = $syncResult['synced'];
            }

            return response()->json([
                'success' => true,
                'message' => 'Appointment updated successfully!',
                'booking' => $this->bookingService->formatBookingForResponse($updatedBooking),
                'google_updated' => $googleUpdated,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Update Appointment Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the appointment.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getBookingLimits(Request $request)
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string|max:20',
            ]);

            $limits = $this->bookingService->getBookingLimits($validated['phone']);

            return response()->json([
                'success' => true,
                'limits' => $limits,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred.',
            ], 500);
        }
    }

    public function getBooking(Request $request, int $id)
    {
        try {
            $booking = $this->bookingService->getBookingById($id);

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'booking' => $this->bookingService->formatBookingForResponse($booking),
            ]);

        } catch (\Exception $e) {
            Log::error('Get booking error', [
                'booking_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve booking.',
            ], 500);
        }
    }

    public function getUpcomingBookings(Request $request)
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string|max:20',
            ]);

            $bookings = $this->bookingService->getUpcomingBookings($validated['phone']);

            return response()->json([
                'success' => true,
                'bookings' => $bookings,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Get upcoming bookings error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve bookings.',
            ], 500);
        }
    }

    public function getStats(Request $request)
    {
        try {
            $stats = $this->bookingRepository->getStats();

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Get stats error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve stats.',
            ], 500);
        }
    }

    public function resyncGoogle(Request $request)
    {
        try {
            $validated = $request->validate([
                'booking_id' => 'nullable|exists:bookings,id',
                'days' => 'nullable|integer|min:1|max:30',
            ]);

            $result = $this->bookingService->resyncGoogleBookings(
                $validated['booking_id'] ?? null,
                $validated['days'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => "Resync completed. Synced: {$result['synced']}, Failed: {$result['failed']}",
                'synced' => $result['synced'],
                'failed' => $result['failed'],
                'total' => $result['total'],
            ]);

        } catch (\Exception $e) {
            Log::error('Google resync error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resync Google Calendar.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}