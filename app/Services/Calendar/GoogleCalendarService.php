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
                        'http://127.0.0.1:8000/google-auth-callback'
                    ]
                ]
            ]);
            
            $this->client->addScope(Calendar::CALENDAR_EVENTS);
            $this->client->addScope(Calendar::CALENDAR);
            $this->client->setAccessType('offline');
            $this->client->setPrompt('select_account consent');

            $token = $this->getToken();
            if ($token) {
                $this->client->setAccessToken($token);
            }

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

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function authenticateWithCode(string $code): void
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        $this->saveToken($token);
    }

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
     * Update an existing booking in Google Calendar
     */
    public function updateBooking(string $eventId, array $updateData): array
    {
        try {
            // Get the existing event
            $event = $this->service->events->get($this->calendarId, $eventId);

            // Update summary/title
            if (isset($updateData['name']) || isset($updateData['service'])) {
                $currentSummary = $event->getSummary();
                $summary = $event->getSummary();
                
                if (isset($updateData['service']) && isset($updateData['name'])) {
                    $summary = $updateData['service'] . ' - ' . $updateData['name'];
                } elseif (isset($updateData['service'])) {
                    // Extract name from current summary or use default
                    $name = $this->extractNameFromSummary($currentSummary) ?? 'Patient';
                    $summary = $updateData['service'] . ' - ' . $name;
                } elseif (isset($updateData['name'])) {
                    // Extract service from current summary or use default
                    $service = $this->extractServiceFromSummary($currentSummary) ?? 'Appointment';
                    $summary = $service . ' - ' . $updateData['name'];
                }
                
                $event->setSummary($summary);
            }

            // Update date/time
            if (isset($updateData['appointment_time'])) {
                $startTime = Carbon::parse($updateData['appointment_time']);
                $duration = $updateData['duration_minutes'] ?? 60;
                $endTime = $startTime->copy()->addMinutes($duration);

                $event->setStart([
                    'dateTime' => $startTime->format('Y-m-d\TH:i:s'),
                    'timeZone' => config('app.timezone', 'UTC'),
                ]);
                $event->setEnd([
                    'dateTime' => $endTime->format('Y-m-d\TH:i:s'),
                    'timeZone' => config('app.timezone', 'UTC'),
                ]);
            }

            // Update description/notes
            if (isset($updateData['notes'])) {
                $currentDescription = $event->getDescription() ?? '';
                $updatedDescription = $this->updateDescription($currentDescription, $updateData);
                $event->setDescription($updatedDescription);
            }

            // Update phone in description
            if (isset($updateData['phone'])) {
                $currentDescription = $event->getDescription() ?? '';
                $updatedDescription = $this->updatePhoneInDescription($currentDescription, $updateData['phone']);
                $event->setDescription($updatedDescription);
            }

            // Update name in description
            if (isset($updateData['name'])) {
                $currentDescription = $event->getDescription() ?? '';
                $updatedDescription = $this->updateNameInDescription($currentDescription, $updateData['name']);
                $event->setDescription($updatedDescription);
            }

            // Update service in description
            if (isset($updateData['service'])) {
                $currentDescription = $event->getDescription() ?? '';
                $updatedDescription = $this->updateServiceInDescription($currentDescription, $updateData['service']);
                $event->setDescription($updatedDescription);
            }

            // Update attendees (email)
            if (isset($updateData['email'])) {
                $attendees = $event->getAttendees() ?? [];
                $found = false;
                
                foreach ($attendees as $attendee) {
                    $email = $attendee->getEmail();
                    // Check if this is the patient's email (not the owner's email)
                    if ($email !== $this->ownerEmail && !str_contains($email, '@example.com')) {
                        $attendee->setEmail($updateData['email']);
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $newAttendee = new \Google\Service\Calendar\EventAttendee();
                    $newAttendee->setEmail($updateData['email']);
                    $attendees[] = $newAttendee;
                }
                
                $event->setAttendees($attendees);
            }

            // Refresh token if expired
            if ($this->client->isAccessTokenExpired()) {
                $refreshToken = $this->client->getRefreshToken();
                if ($refreshToken) {
                    $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $this->saveToken($this->client->getAccessToken());
                }
            }

            // Update the event
            $updatedEvent = $this->service->events->update(
                $this->calendarId,
                $eventId,
                $event,
                ['sendUpdates' => 'all']
            );

            Log::info('Google Calendar event updated', [
                'event_id' => $eventId,
                'event_link' => $updatedEvent->getHtmlLink(),
                'updates' => array_keys($updateData)
            ]);

            return [
                'success' => true,
                'event_id' => $updatedEvent->getId(),
                'event_link' => $updatedEvent->getHtmlLink(),
            ];

        } catch (\Exception $e) {
            Log::error('Google Calendar update failed', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

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

    protected function getToken()
    {
        $tokenPath = storage_path('google/token.json');
        if (file_exists($tokenPath)) {
            return json_decode(file_get_contents($tokenPath), true);
        }
        return null;
    }

    protected function saveToken(array $token)
    {
        $tokenPath = storage_path('google/token.json');
        file_put_contents($tokenPath, json_encode($token));
    }

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

    /**
     * Extract name from event summary
     */
    protected function extractNameFromSummary(?string $summary): ?string
    {
        if (empty($summary)) return null;
        
        $parts = explode(' - ', $summary);
        return count($parts) > 1 ? $parts[1] : $parts[0];
    }

    /**
     * Extract service from event summary
     */
    protected function extractServiceFromSummary(?string $summary): ?string
    {
        if (empty($summary)) return null;
        
        $parts = explode(' - ', $summary);
        return count($parts) > 1 ? $parts[0] : null;
    }

    /**
     * Update description with new data
     */
    protected function updateDescription(string $description, array $data): string
    {
        $lines = explode("\n", $description);
        $updatedLines = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, '📝 Notes:')) {
                if (isset($data['notes'])) {
                    $updatedLines[] = '📝 Notes:';
                    $updatedLines[] = $data['notes'];
                }
            } elseif (str_starts_with($line, '👤 Patient:')) {
                if (isset($data['name'])) {
                    $updatedLines[] = '👤 Patient: ' . $data['name'];
                } else {
                    $updatedLines[] = $line;
                }
            } elseif (str_starts_with($line, '📱 Phone:')) {
                if (isset($data['phone'])) {
                    $updatedLines[] = '📱 Phone: ' . $data['phone'];
                } else {
                    $updatedLines[] = $line;
                }
            } elseif (str_starts_with($line, '🏥 Service:')) {
                if (isset($data['service'])) {
                    $updatedLines[] = '🏥 Service: ' . $data['service'];
                } else {
                    $updatedLines[] = $line;
                }
            } elseif (str_starts_with($line, '✉️ Email:')) {
                if (isset($data['email'])) {
                    $updatedLines[] = '✉️ Email: ' . $data['email'];
                } else {
                    $updatedLines[] = $line;
                }
            } else {
                $updatedLines[] = $line;
            }
        }

        return implode("\n", $updatedLines);
    }

    /**
     * Update phone number in description
     */
    protected function updatePhoneInDescription(string $description, string $phone): string
    {
        $lines = explode("\n", $description);
        
        foreach ($lines as &$line) {
            if (str_starts_with($line, '📱 Phone:')) {
                $line = '📱 Phone: ' . $phone;
                break;
            }
        }
        
        return implode("\n", $lines);
    }

    /**
     * Update name in description
     */
    protected function updateNameInDescription(string $description, string $name): string
    {
        $lines = explode("\n", $description);
        
        foreach ($lines as &$line) {
            if (str_starts_with($line, '👤 Patient:')) {
                $line = '👤 Patient: ' . $name;
                break;
            }
        }
        
        return implode("\n", $lines);
    }

    /**
     * Update service in description
     */
    protected function updateServiceInDescription(string $description, string $service): string
    {
        $lines = explode("\n", $description);
        
        foreach ($lines as &$line) {
            if (str_starts_with($line, '🏥 Service:')) {
                $line = '🏥 Service: ' . $service;
                break;
            }
        }
        
        return implode("\n", $lines);
    }
}