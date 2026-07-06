<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\CallLog;
use App\Models\Booking;
use App\Repositories\LeadRepository;
use App\Repositories\CallLogRepository;
use App\Repositories\BookingRepository;
use App\Services\Calendar\CalendlyService;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VapiWebhookController extends Controller
{
    protected LeadRepository $leadRepository;
    protected CallLogRepository $callLogRepository;
    protected BookingRepository $bookingRepository;
    protected CalendlyService $calendly;
    protected NotificationService $notificationService;

    public function __construct(
        LeadRepository $leadRepository,
        CallLogRepository $callLogRepository,
        BookingRepository $bookingRepository,
        CalendlyService $calendly,
        NotificationService $notificationService
    ) {
        $this->leadRepository = $leadRepository;
        $this->callLogRepository = $callLogRepository;
        $this->bookingRepository = $bookingRepository;
        $this->calendly = $calendly;
        $this->notificationService = $notificationService;
    }

    /**
     * Handle Vapi webhook
     * 
     * Vapi sends different event types:
     * - call.started
     * - call.ended
     * - call.status.updated
     * - conversation.message
     * - conversation.update
     * - booking.confirmed (custom event if you have one)
     */
    public function handle(Request $request)
    {
        try {
            $payload = $request->all();
            Log::info('Vapi Webhook Received', ['payload' => $payload]);

            $event = $payload['event'] ?? null;
            $callId = $payload['callId'] ?? $payload['id'] ?? null;

            if (!$event || !$callId) {
                Log::warning('Invalid webhook payload - missing event or callId', ['payload' => $payload]);
                return response()->json(['status' => 'error', 'message' => 'Invalid payload'], 400);
            }

            // Find or create call log
            $callLog = $this->callLogRepository->findByCallId($callId);
            
            if (!$callLog) {
                // Create new call log if it doesn't exist
                $callLog = $this->createCallLogFromWebhook($payload);
            }

            // Handle different event types
            switch ($event) {
                case 'call.started':
                    $this->handleCallStarted($callLog, $payload);
                    break;

                case 'call.ended':
                    $this->handleCallEnded($callLog, $payload);
                    break;

                case 'call.status.updated':
                    $this->handleCallStatusUpdated($callLog, $payload);
                    break;

                case 'conversation.message':
                    $this->handleConversationMessage($callLog, $payload);
                    break;

                case 'conversation.update':
                    $this->handleConversationUpdate($callLog, $payload);
                    break;

                case 'booking.confirmed':
                case 'booking.created':
                    $this->handleBookingCreated($callLog, $payload);
                    break;

                default:
                    Log::info('Unhandled Vapi event', ['event' => $event, 'call_id' => $callId]);
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Vapi Webhook Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a call log from webhook data
     */
    protected function createCallLogFromWebhook(array $payload): CallLog
    {
        $callId = $payload['callId'] ?? $payload['id'] ?? null;
        $customerNumber = $payload['customer']?->number ?? $payload['from'] ?? null;
        $customerName = $payload['customer']?->name ?? $payload['customerName'] ?? 'Unknown';

        // Find or create lead
        $lead = null;
        if ($customerNumber) {
            $lead = $this->leadRepository->findByPhone($customerNumber);
            if (!$lead) {
                $lead = $this->leadRepository->create([
                    'phone' => $customerNumber,
                    'name' => $customerName,
                    'source' => 'vapi_inbound',
                    'status' => 'new',
                ]);
            }
        }

        // Create call log
        $callLog = $this->callLogRepository->create([
            'call_id' => $callId,
            'lead_id' => $lead?->id,
            'direction' => $payload['direction'] ?? 'inbound',
            'status' => 'initiated',
            'duration' => 0,
            'transcript' => [],
            'summary' => null,
            'intent' => $payload['intent'] ?? null,
            'metadata' => [
                'vapi_data' => $payload,
                'received_at' => now()->toDateTimeString(),
            ],
        ]);

        return $callLog;
    }

    /**
     * Handle call.started event
     */
    protected function handleCallStarted(CallLog $callLog, array $payload)
    {
        $this->callLogRepository->update($callLog, [
            'status' => 'in_progress',
            'metadata' => array_merge($callLog->metadata ?? [], [
                'started_at' => now()->toDateTimeString(),
                'vapi_data' => $payload,
            ]),
        ]);
    }

    /**
     * Handle call.ended event
     */
    protected function handleCallEnded(CallLog $callLog, array $payload)
    {
        $duration = $payload['duration'] ?? $callLog->duration ?? 0;
        $status = $payload['status'] ?? 'completed';

        $this->callLogRepository->update($callLog, [
            'status' => $status,
            'duration' => $duration,
            'metadata' => array_merge($callLog->metadata ?? [], [
                'ended_at' => now()->toDateTimeString(),
                'vapi_data' => $payload,
            ]),
        ]);

        // Update lead last call
        if ($callLog->lead_id) {
            $this->leadRepository->updateLastCall($callLog->lead);
        }

        // Check if booking was created during this call
        $this->checkForBooking($callLog, $payload);
    }

    /**
     * Handle call.status.updated event
     */
    protected function handleCallStatusUpdated(CallLog $callLog, array $payload)
    {
        $status = $payload['status'] ?? $callLog->status;
        $this->callLogRepository->update($callLog, [
            'status' => $status,
            'metadata' => array_merge($callLog->metadata ?? [], [
                'status_updated_at' => now()->toDateTimeString(),
                'vapi_data' => $payload,
            ]),
        ]);
    }

    /**
     * Handle conversation.message event
     * This is where we store individual messages
     */
    protected function handleConversationMessage(CallLog $callLog, array $payload)
    {
        $message = $payload['message'] ?? null;
        $role = $payload['role'] ?? 'unknown';
        $timestamp = $payload['timestamp'] ?? now()->toDateTimeString();

        if (!$message) {
            return;
        }

        // Get current transcript
        $transcript = $callLog->transcript ?? [];

        // Add new message
        $transcript[] = [
            'role' => $role,
            'content' => $message,
            'timestamp' => $timestamp,
        ];

        // Update transcript
        $this->callLogRepository->update($callLog, [
            'transcript' => $transcript,
        ]);

        // Check for booking intent in the message
        $this->detectBookingIntent($callLog, $message);
    }

    /**
     * Handle conversation.update event
     * This usually contains the final transcript and summary
     */
    protected function handleConversationUpdate(CallLog $callLog, array $payload)
    {
        $transcript = $payload['transcript'] ?? $callLog->transcript ?? [];
        $summary = $payload['summary'] ?? null;
        $intent = $payload['intent'] ?? $callLog->intent ?? null;

        // Format transcript if needed
        if (is_array($transcript) && isset($transcript['messages'])) {
            $transcript = $transcript['messages'];
        }

        $this->callLogRepository->update($callLog, [
            'transcript' => $transcript,
            'summary' => $summary,
            'intent' => $intent,
            'metadata' => array_merge($callLog->metadata ?? [], [
                'conversation_updated_at' => now()->toDateTimeString(),
                'vapi_data' => $payload,
            ]),
        ]);

        // Check for booking
        $this->checkForBooking($callLog, $payload);
    }

    /**
     * Handle booking.created or booking.confirmed event
     */
    protected function handleBookingCreated(CallLog $callLog, array $payload)
    {
        $bookingData = $payload['booking'] ?? $payload['data'] ?? [];

        if (empty($bookingData)) {
            Log::warning('Booking event received but no booking data', ['payload' => $payload]);
            return;
        }

        $this->createBookingFromData($callLog, $bookingData);
    }

    /**
     * Detect booking intent from conversation messages
     */
    protected function detectBookingIntent(CallLog $callLog, string $message)
    {
        // Check for booking keywords
        $bookingKeywords = ['book', 'appointment', 'schedule', 'reserve', 'confirm booking'];
        $messageLower = strtolower($message);

        foreach ($bookingKeywords as $keyword) {
            if (strpos($messageLower, $keyword) !== false) {
                // Update intent
                if (!$callLog->intent || $callLog->intent === 'unknown') {
                    $this->callLogRepository->update($callLog, [
                        'intent' => 'booking_requested',
                    ]);
                }
                break;
            }
        }
    }

    /**
     * Check if booking was created during the call
     */
    protected function checkForBooking(CallLog $callLog, array $payload)
    {
        // Check if booking data exists in payload
        $bookingData = $payload['booking'] ?? $payload['booking_data'] ?? null;

        if ($bookingData) {
            $this->createBookingFromData($callLog, $bookingData);
        }

        // Check if booking was mentioned in the conversation
        if ($callLog->intent === 'booking_requested' && $callLog->lead_id) {
            // Try to extract booking info from transcript
            $this->extractBookingFromTranscript($callLog);
        }
    }

    /**
     * Create a booking from data
     */
    protected function createBookingFromData(CallLog $callLog, array $bookingData)
    {
        if (!$callLog->lead_id) {
            Log::warning('Cannot create booking - no lead associated with call', ['call_id' => $callLog->call_id]);
            return;
        }

        try {
            // Check if booking already exists
            $existingBooking = $this->bookingRepository->findByCallId($callLog->call_id);
            if ($existingBooking) {
                Log::info('Booking already exists for this call', ['call_id' => $callLog->call_id]);
                return;
            }

            $appointmentTime = $bookingData['appointment_time'] ?? $bookingData['time'] ?? null;
            if (!$appointmentTime) {
                Log::warning('Booking data missing appointment time', ['booking_data' => $bookingData]);
                return;
            }

            // Create booking via Calendly
            $lead = $this->leadRepository->findById($callLog->lead_id);
            $booking = $this->calendly->bookAppointment(
                $lead,
                $appointmentTime,
                $bookingData['service'] ?? 'Consultation',
                $bookingData['duration_minutes'] ?? 30
            );

            // Associate booking with call log
            $this->callLogRepository->update($callLog, [
                'booking_id' => $booking->id,
                'metadata' => array_merge($callLog->metadata ?? [], [
                    'booking_created_at' => now()->toDateTimeString(),
                    'booking_data' => $bookingData,
                ]),
            ]);

            // Update lead status
            $this->leadRepository->update($lead, ['status' => 'booked']);

            // Send notification
            $this->notificationService->sendAppointmentConfirmation($booking);

            Log::info('Booking created from webhook', [
                'booking_id' => $booking->id,
                'lead_id' => $lead->id,
                'call_id' => $callLog->call_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create booking from webhook', [
                'error' => $e->getMessage(),
                'call_id' => $callLog->call_id,
                'booking_data' => $bookingData,
            ]);
        }
    }

    /**
     * Try to extract booking information from transcript
     */
    protected function extractBookingFromTranscript(CallLog $callLog)
    {
        $transcript = $callLog->transcript ?? [];
        if (empty($transcript)) {
            return;
        }

        // Look for date/time patterns in the conversation
        $fullConversation = implode(' ', array_column($transcript, 'content'));
        
        // Simple pattern matching for dates
        $patterns = [
            '/(\d{1,2}\/\d{1,2}\/\d{4})/', // 12/25/2024
            '/(\d{1,2}-\d{1,2}-\d{4})/', // 12-25-2024
            '/(\d{4}-\d{1,2}-\d{1,2})/', // 2024-12-25
            '/(\d{1,2}:\d{2}\s*(?:AM|PM))/i', // 2:30 PM
        ];

        $extractedData = [
            'date' => null,
            'time' => null,
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $fullConversation, $matches)) {
                // Store extracted data
                Log::info('Extracted potential booking info from transcript', [
                    'call_id' => $callLog->call_id,
                    'matched' => $matches[0],
                    'pattern' => $pattern,
                ]);
                break;
            }
        }

        // You could create a pending booking or flag for manual review here
        // For now, just log it
        Log::info('Booking extraction completed', [
            'call_id' => $callLog->call_id,
            'extracted' => $extractedData,
        ]);
    }
}