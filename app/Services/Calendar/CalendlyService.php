<?php

namespace App\Services\Calendar;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CalendlyService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.calendly.com';
    protected ?string $userUri = null;
    protected ?string $organizationUri = null;

    public function __construct()
    {
        $this->apiKey = config('services.calendly.api_key');
        
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Calendly API key not configured.');
        }
        
        Log::info('CalendlyService initialized', [
            'api_key_exists' => !empty($this->apiKey),
            'api_key_length' => strlen($this->apiKey)
        ]);
    }

    /**
     * Get the current user's URI
     */
    public function getUserUri(): string
    {
        if ($this->userUri) {
            return $this->userUri;
        }

        $cacheKey = 'calendly_user_uri';
        if (Cache::has($cacheKey)) {
            $this->userUri = Cache::get($cacheKey);
            return $this->userUri;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/users/me');

            if (!$response->successful()) {
                Log::error('Failed to get Calendly user', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                throw new \RuntimeException('Failed to authenticate with Calendly: ' . $response->body());
            }

            $data = $response->json();
            
            if (!isset($data['resource']['uri'])) {
                throw new \RuntimeException('Invalid response from Calendly API');
            }
            
            $this->userUri = $data['resource']['uri'];
            Cache::put($cacheKey, $this->userUri, now()->addHours(24));
            
            Log::info('Calendly user URI retrieved', ['user_uri' => $this->userUri]);
            
            return $this->userUri;
            
        } catch (\Exception $e) {
            Log::error('Calendly getUserUri error', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get event types for the user
     */
    public function getEventTypes(array $params = []): array
    {
        try {
            $userUri = $this->getUserUri();
            
            $queryParams = array_merge([
                'user' => $userUri,
                'active' => 'true',
                'count' => 100,
            ], $params);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/event_types', $queryParams);

            if (!$response->successful()) {
                Log::error('Failed to get event types', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return [];
            }

            $data = $response->json();
            return $data['collection'] ?? [];
            
        } catch (\Exception $e) {
            Log::error('Calendly getEventTypes error', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get event type for service
     */
    public function getEventTypeForService(string $service): ?string
    {
        try {
            // Check config mapping
            $eventTypeMap = config('services.calendly.event_type_mapping', []);
            
            if (isset($eventTypeMap[$service])) {
                $configUri = $eventTypeMap[$service];
                Log::info('Using mapped event type from config', [
                    'service' => $service,
                    'event_type' => $configUri
                ]);
                return $configUri;
            }
            
            // Search dynamically
            $eventTypes = $this->getEventTypes();
            
            foreach ($eventTypes as $type) {
                if (isset($type['name']) && stripos($type['name'], $service) !== false) {
                    Log::info('Found event type by name', [
                        'service' => $service,
                        'event_type' => $type['uri']
                    ]);
                    return $type['uri'];
                }
            }
            
            // Use first available
            if (!empty($eventTypes)) {
                return $eventTypes[0]['uri'];
            }
            
            // Fallback to default
            return config('services.calendly.default_event_type');
            
        } catch (\Exception $e) {
            Log::error('Calendly getEventTypeForService error', [
                'error' => $e->getMessage(),
                'service' => $service
            ]);
            return null;
        }
    }

    /**
     * Create a Calendly event booking - FIXED VERSION
     */
    public function createEventBooking(string $eventTypeUri, string $startTime, string $endTime, array $invitee, array $questions = []): array
    {
        try {
            // Validate required fields
            if (empty($invitee['email'])) {
                throw new \RuntimeException('Invitee email is required');
            }
            
            if (empty($invitee['name'])) {
                throw new \RuntimeException('Invitee name is required');
            }

            if (empty($invitee['timezone'])) {
                $invitee['timezone'] = config('app.timezone', 'UTC');
            }

            // Format the payload - IMPORTANT: Use the correct format
            $payload = [
                'event_type' => $eventTypeUri,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'invitee' => [
                    'email' => $invitee['email'],
                    'name' => $invitee['name'],
                    'timezone' => $invitee['timezone'],
                ],
                'questions_and_answers' => $questions,
            ];

            Log::info('Creating Calendly event', [
                'event_type' => $eventTypeUri,
                'invitee_email' => $invitee['email'],
                'start_time' => $startTime,
                'end_time' => $endTime
            ]);

            // Make the API call
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->baseUrl . '/scheduled_events', $payload);

            Log::info('Calendly create event response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body()
            ]);

            if (!$response->successful()) {
                $errorMessage = 'Failed to create Calendly event';
                $responseData = $response->json();
                
                if (isset($responseData['title'])) {
                    $errorMessage .= ': ' . $responseData['title'];
                }
                if (isset($responseData['message'])) {
                    $errorMessage .= ' - ' . $responseData['message'];
                }
                
                Log::error('Failed to create Calendly event', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'payload' => $payload
                ]);
                
                throw new \RuntimeException($errorMessage);
            }

            $result = $response->json();
            
            if (!isset($result['resource'])) {
                throw new \RuntimeException('Invalid response from Calendly API');
            }

            Log::info('Calendly event created successfully', [
                'event_uri' => $result['resource']['uri'] ?? null,
                'event_id' => $result['resource']['id'] ?? null
            ]);

            return $result['resource'];
            
        } catch (\Exception $e) {
            Log::error('Calendly createEventBooking error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Cancel a Calendly event
     */
    public function cancelEvent(string $eventUri, string $reason = 'Cancelled by customer'): bool
    {
        try {
            Log::info('Cancelling Calendly event', [
                'event_uri' => $eventUri,
                'reason' => $reason
            ]);

            // Extract UUID from URI
            $uuid = $this->extractUuid($eventUri);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/scheduled_events/' . $uuid . '/cancellation', [
                'reason' => $reason,
            ]);

            if (!$response->successful()) {
                Log::error('Failed to cancel Calendly event', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'event_uri' => $eventUri
                ]);
                throw new \RuntimeException('Failed to cancel Calendly event: ' . $response->body());
            }

            Log::info('Calendly event cancelled successfully', [
                'event_uri' => $eventUri
            ]);

            return true;
            
        } catch (\Exception $e) {
            Log::error('Calendly cancelEvent error', [
                'error' => $e->getMessage(),
                'event_uri' => $eventUri
            ]);
            throw $e;
        }
    }

    /**
     * Extract UUID from Calendly URI
     */
    protected function extractUuid(string $uri): string
    {
        $parts = explode('/', $uri);
        return end($parts);
    }

    /**
     * Get event details by URI
     */
    public function getEvent(string $eventUri): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($eventUri);

            if (!$response->successful()) {
                throw new \RuntimeException('Failed to get event details: ' . $response->body());
            }

            return $response->json()['resource'];
            
        } catch (\Exception $e) {
            Log::error('Calendly getEvent error', [
                'error' => $e->getMessage(),
                'event_uri' => $eventUri
            ]);
            throw $e;
        }
    }
}