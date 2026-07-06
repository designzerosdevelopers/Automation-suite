<?php

namespace App\Services\Calendar;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GoogleCalendarService
{
    protected Client $client;
    protected Calendar $service;
    protected string $calendarId;
    protected string $ownerEmail;

    public function __construct()
    {
        $this->calendarId = config('services.google.calendar_id', 'primary');
        $this->ownerEmail = config('services.google.calendar_owner_email');

        if (empty($this->ownerEmail)) {
            throw new \RuntimeException('Google Calendar owner email not configured.');
        }

        try {
            $this->client = new Client();
            
            // 🔥 HARDCODE THE OAUTH CREDENTIALS DIRECTLY 🔥
            $this->client->setAuthConfig([
                'installed' => [
                    'client_id' => '646210139527-r84lr23j9i6nn7qc4e4f45gutnmpoggl.apps.googleusercontent.com',
                    'project_id' => 'gen-lang-client-0560204066',
                    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                    'token_uri' => 'https://oauth2.googleapis.com/token',
                    'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                    'client_secret' => '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',
                    'redirect_uris' => [
                        'http://127.0.0.1:8000',
                        'http://127.0.0.1:8000/google-auth-callback'  // ← ADDED
                    ]
                ]
            ]);
            
            $this->client->addScope(Calendar::CALENDAR_EVENTS);
            $this->client->addScope(Calendar::CALENDAR);
            $this->client->setAccessType('offline');
            $this->client->setPrompt('select_account consent');

            // Load existing token
            $token = $this->getToken();
            if ($token) {
                $this->client->setAccessToken($token);
            }

            // Refresh token if expired
            if ($this->client->isAccessTokenExpired()) {
                $refreshToken = $this->client->getRefreshToken();
                if ($refreshToken) {
                    $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $this->saveToken($this->client->getAccessToken());
                }
            }

            $this->service = new Calendar($this->client);

            Log::info('GoogleCalendarService initialized (OAuth 2.0 - Hardcoded)', [
                'owner_email' => $this->ownerEmail,
                'calendar_id' => $this->calendarId
            ]);

        } catch (\Exception $e) {
            Log::error('GoogleCalendarService initialization failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get authorization URL (run once to get token)
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Exchange authorization code for token
     */
    public function authenticateWithCode(string $code): void
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        $this->saveToken($token);
    }

    /**
     * Create a booking in Google Calendar
     */
    public function createBooking(array $bookingData): array
    {
        try {
            $startTime = Carbon::parse($bookingData['appointment_time']);
            $duration = $bookingData['duration_minutes'] ?? 60;
            $endTime = $startTime->copy()->addMinutes($duration);

            $event = new Event([
                'summary' => ($bookingData['service'] ?? 'Appointment') . ' - ' . ($bookingData['name'] ?? 'Patient'),
                'description' => $this->formatDescription($bookingData),
                'location' => $bookingData['location'] ?? 'Phone Call',
                'start' => [
                    'dateTime' => $startTime->format('Y-m-d\TH:i:s'),
                    'timeZone' => config('app.timezone', 'UTC'),
                ],
                'end' => [
                    'dateTime' => $endTime->format('Y-m-d\TH:i:s'),
                    'timeZone' => config('app.timezone', 'UTC'),
                ],
                'attendees' => $this->formatAttendees($bookingData),
                'reminders' => [
                    'useDefault' => false,
                    'overrides' => [
                        ['method' => 'email', 'minutes' => 24 * 60],
                        ['method' => 'popup', 'minutes' => 30],
                    ],
                ],
                'colorId' => 1,
            ]);

            Log::info('Creating Google Calendar event', [
                'summary' => $event->getSummary(),
                'start_time' => $startTime->toIso8601String()
            ]);

            // Refresh token if expired
            if ($this->client->isAccessTokenExpired()) {
                $refreshToken = $this->client->getRefreshToken();
                if ($refreshToken) {
                    $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $this->saveToken($this->client->getAccessToken());
                }
            }

            $createdEvent = $this->service->events->insert(
                $this->calendarId,
                $event,
                ['sendUpdates' => 'all']
            );

            Log::info('Google Calendar event created', [
                'event_id' => $createdEvent->getId(),
                'event_link' => $createdEvent->getHtmlLink()
            ]);

            return [
                'success' => true,
                'event_id' => $createdEvent->getId(),
                'event_link' => $createdEvent->getHtmlLink(),
            ];

        } catch (\Exception $e) {
            Log::error('Google Calendar event creation failed', [
                'error' => $e->getMessage(),
                'booking_data' => $bookingData
            ]);
            throw $e;
        }
    }

    /**
     * Cancel a booking from Google Calendar
     */
    public function cancelBooking(string $eventId): bool
    {
        try {
            $this->service->events->delete($this->calendarId, $eventId);
            
            Log::info('Google Calendar event cancelled', [
                'event_id' => $eventId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Google Calendar event cancellation failed', [
                'error' => $e->getMessage(),
                'event_id' => $eventId
            ]);
            throw $e;
        }
    }

    /**
     * Get stored token
     */
    protected function getToken()
    {
        $tokenPath = storage_path('google/token.json');
        if (file_exists($tokenPath)) {
            return json_decode(file_get_contents($tokenPath), true);
        }
        return null;
    }

    /**
     * Save token to file
     */
    protected function saveToken(array $token)
    {
        $tokenPath = storage_path('google/token.json');
        file_put_contents($tokenPath, json_encode($token));
    }

    /**
     * Format description for Google Calendar
     */
    protected function formatDescription(array $data): string
    {
        $description = "📋 APPOINTMENT DETAILS\n";
        $description .= "═══════════════════════════\n\n";
        $description .= "👤 Patient: " . ($data['name'] ?? 'N/A') . "\n";
        $description .= "📱 Phone: " . ($data['phone'] ?? 'N/A') . "\n";
        $description .= "🏥 Service: " . ($data['service'] ?? 'N/A') . "\n";
        
        if (!empty($data['email'])) {
            $description .= "✉️ Email: " . $data['email'] . "\n";
        }
        
        if (!empty($data['notes'])) {
            $description .= "\n📝 Notes:\n" . $data['notes'] . "\n";
        }
        
        $description .= "\n═══════════════════════════\n";
        $description .= "🔗 Booked via Aesthetic Clinic AI";
        
        return $description;
    }

    /**
     * Format attendees for Google Calendar
     */
    protected function formatAttendees(array $data): array
    {
        $attendees = [];

        if (!empty($data['email'])) {
            $attendees[] = ['email' => $data['email']];
        }

        if (empty($data['email']) && !empty($data['phone'])) {
            $attendees[] = ['email' => $data['phone'] . '@example.com'];
        }

        $attendees[] = ['email' => $this->ownerEmail];

        return $attendees;
    }
}