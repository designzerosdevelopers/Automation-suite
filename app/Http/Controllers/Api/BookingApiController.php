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

    /**
     * Check availability for a specific time slot
     */
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

    /**
     * Book an appointment
     */
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

    /**
     * Cancel an appointment
     */
    public function cancelAppointment(Request $request)
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string|max:20',
            ]);

            $booking = $this->bookingService->findBookingByPhone($validated['phone']);

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'No upcoming booking found for this phone number.',
                ], 404);
            }

            $result = $this->bookingService->cancelBooking($booking, 'Cancelled via Vapi AI');

            return response()->json([
                'success' => true,
                'message' => 'Appointment cancelled successfully!',
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

    /**
     * Resync failed Google Calendar bookings
     */
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

    /**
     * Get booking details
     */
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

    /**
     * Get upcoming bookings for a phone number
     */
    public function getUpcomingBookings(Request $request)
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string|max:20',
            ]);

            $bookings = $this->bookingService->getUpcomingBookings($validated['phone']);

            return response()->json([
                'success' => true,
                'bookings' => $this->bookingService->formatBookingsForResponse(collect($bookings)),
            ]);

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
}