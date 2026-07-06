<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Repositories\BookingRepository;
use App\Repositories\CallLogRepository;
use App\Services\Calendar\GoogleCalendarService;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BookingController extends Controller
{
    protected BookingRepository $bookingRepository;
    protected CallLogRepository $callLogRepository;
    protected GoogleCalendarService $googleCalendar;
    protected NotificationService $notifications;

    public function __construct(
        BookingRepository $bookingRepository,
        CallLogRepository $callLogRepository,
        GoogleCalendarService $googleCalendar,
        NotificationService $notifications
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->callLogRepository = $callLogRepository;
        $this->googleCalendar = $googleCalendar;
        $this->notifications = $notifications;
    }

    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status');

        $bookings = $this->bookingRepository->getUpcoming();
        $todayBookings = $this->bookingRepository->getToday();

        return view('admin.bookings.index', compact('bookings', 'todayBookings'));
    }

    public function create(Request $request)
    {
        $callId = $request->get('call_id');
        $selectedCall = null;
        
        if ($callId) {
            $selectedCall = $this->callLogRepository->findById($callId);
        }

        return view('admin.bookings.create', compact('selectedCall', 'callId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'service' => 'required|string|max:255',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'duration_minutes' => 'nullable|integer|min:15',
            'notes' => 'nullable|string',
            'call_id' => 'nullable|string',
            'status' => 'nullable|in:pending,confirmed,completed,cancelled',
        ]);

        $appointmentDateTime = $validated['appointment_date'] . ' ' . $validated['appointment_time'];
        $duration = $validated['duration_minutes'] ?? config('ai-receptionist.booking.default_duration_minutes', 60);

        // Check availability
        $isAvailable = $this->checkAvailability($appointmentDateTime);
        if (!$isAvailable) {
            return back()->with('error', 'This time slot is not available.')->withInput();
        }

        // Create booking
        $booking = $this->bookingRepository->create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'service' => $validated['service'],
            'appointment_time' => $appointmentDateTime,
            'duration_minutes' => $duration,
            'notes' => $validated['notes'] ?? null,
            'call_id' => $validated['call_id'] ?? null,
            'status' => $validated['status'] ?? 'confirmed',
            'google_sync_status' => 'pending',
        ]);

        // Sync with Google Calendar
        try {
            $googleResult = $this->googleCalendar->createBooking([
                'name' => $booking->name,
                'phone' => $booking->phone,
                'email' => $booking->email,
                'service' => $booking->service,
                'appointment_time' => $booking->appointment_time,
                'duration_minutes' => $booking->duration_minutes,
                'notes' => $booking->notes,
                'booking_id' => $booking->id,
            ]);

            $booking->google_event_id = $googleResult['event_id'];
            $booking->google_event_link = $googleResult['event_link'];
            $booking->google_sync_status = 'synced';
            $booking->save();

        } catch (\Exception $e) {
            Log::error('Google Calendar sync failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
            $booking->google_sync_status = 'failed';
            $booking->google_sync_error = substr($e->getMessage(), 0, 500);
            $booking->save();
        }

        // Send notification
        try {
            $this->notifications->sendAppointmentConfirmation($booking);
        } catch (\Exception $e) {
            Log::error('Notification failed', ['error' => $e->getMessage()]);
        }

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking created successfully!');
    }

    public function show(Booking $booking)
    {
        $booking->load(['callLog']);
        return view('admin.bookings.show', compact('booking'));
    }

    public function edit(Booking $booking)
    {
        $booking->load(['callLog']);
        return view('admin.bookings.edit', compact('booking'));
    }

    public function update(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'service' => 'required|string|max:255',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'duration_minutes' => 'nullable|integer|min:15',
            'notes' => 'nullable|string',
            'call_id' => 'nullable|string',
            'status' => 'nullable|in:pending,confirmed,completed,cancelled',
        ]);

        $newDateTime = $validated['appointment_date'] . ' ' . $validated['appointment_time'];
        $oldDateTime = $booking->appointment_time;

        // Check availability if time changed
        if ($newDateTime != $oldDateTime) {
            $isAvailable = $this->checkAvailability($newDateTime, $booking->id);
            if (!$isAvailable) {
                return back()->with('error', 'This time slot is not available.')->withInput();
            }
        }

        // Update booking
        $this->bookingRepository->update($booking, [
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'service' => $validated['service'],
            'appointment_time' => $newDateTime,
            'duration_minutes' => $validated['duration_minutes'] ?? $booking->duration_minutes,
            'notes' => $validated['notes'] ?? null,
            'call_id' => $validated['call_id'] ?? null,
            'status' => $validated['status'] ?? $booking->status,
        ]);

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking updated successfully!');
    }

    public function destroy(Booking $booking)
    {
        // Cancel in Google Calendar
        if ($booking->google_event_id) {
            try {
                $this->googleCalendar->cancelBooking($booking->google_event_id);
            } catch (\Exception $e) {
                Log::error('Failed to cancel Google Calendar event', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->bookingRepository->delete($booking);

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking deleted successfully!');
    }

    public function confirm(Booking $booking)
    {
        $booking->confirm();
        return back()->with('success', 'Booking confirmed!');
    }

    public function complete(Booking $booking)
    {
        $booking->complete();
        return back()->with('success', 'Booking marked as completed!');
    }

    public function reschedule(Request $request, Booking $booking)
    {
        $request->validate([
            'appointment_time' => 'required|date|after:now',
            'duration_minutes' => 'nullable|integer|min:15',
        ]);

        $available = $this->checkAvailability($request->appointment_time, $booking->id);

        if (!$available) {
            return back()->with('error', 'This time slot is not available.');
        }

        $this->bookingRepository->update($booking, [
            'appointment_time' => $request->appointment_time,
            'duration_minutes' => $request->duration_minutes ?? $booking->duration_minutes,
        ]);

        // Re-sync with Google Calendar
        if ($booking->google_event_id) {
            try {
                $this->googleCalendar->cancelBooking($booking->google_event_id);
                
                $googleResult = $this->googleCalendar->createBooking([
                    'name' => $booking->name,
                    'phone' => $booking->phone,
                    'email' => $booking->email,
                    'service' => $booking->service,
                    'appointment_time' => $booking->appointment_time,
                    'duration_minutes' => $booking->duration_minutes,
                    'notes' => $booking->notes,
                    'booking_id' => $booking->id,
                ]);

                $booking->google_event_id = $googleResult['event_id'];
                $booking->google_event_link = $googleResult['event_link'];
                $booking->google_sync_status = 'synced';
                $booking->save();

            } catch (\Exception $e) {
                Log::error('Google Calendar reschedule failed', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return back()->with('success', 'Booking rescheduled successfully!');
    }

    protected function checkAvailability($datetime, $excludeBookingId = null)
    {
        $query = Booking::where('appointment_time', $datetime)
            ->where('status', '!=', 'cancelled');

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return !$query->exists();
    }
}