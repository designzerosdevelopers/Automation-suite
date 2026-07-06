<?php

namespace App\Services\Voice;

use App\Contracts\Services\VoiceServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VapiService implements VoiceServiceInterface
{
    protected string $apiKey;
    protected string $assistantId;
    protected string $phoneNumberId;
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('ai-receptionist.vapi.api_key');
        $this->assistantId = config('ai-receptionist.vapi.assistant_id');
        $this->phoneNumberId = config('ai-receptionist.vapi.phone_number_id');
        $this->apiUrl = config('ai-receptionist.vapi.api_url', 'https://api.vapi.ai');
    }

    public function makeCall(string $phone, string $name, ?string $message = null): array
    {
        $payload = [
            'assistantId' => $this->assistantId,
            'phoneNumberId' => $this->phoneNumberId,
            'customer' => [
                'number' => $phone,
                'name' => $name,
            ],
        ];

        if ($message) {
            $payload['context'] = [
                'message' => $message,
                'clinic_name' => config('ai-receptionist.clinic.name'),
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->post("{$this->apiUrl}/call", $payload);

        if ($response->failed()) {
            Log::error('Vapi call failed', [
                'phone' => $phone,
                'error' => $response->body(),
            ]);
            throw new \Exception('Call failed: ' . $response->body());
        }

        return $response->json();
    }

    public function handleWebhook(array $payload): array
    {
        // Process incoming call data
        $callId = $payload['call_id'] ?? null;
        $caller = $payload['caller'] ?? [];
        $transcript = $payload['transcript'] ?? [];
        $summary = $payload['summary'] ?? null;
        $intent = $payload['intent'] ?? 'unknown';
        $status = $payload['status'] ?? 'completed';
        $duration = $payload['duration'] ?? 0;
        $cost = $payload['cost'] ?? 0;

        return [
            'call_id' => $callId,
            'caller' => $caller,
            'transcript' => $transcript,
            'summary' => $summary,
            'intent' => $intent,
            'status' => $status,
            'duration' => $duration,
            'cost' => $cost,
            'metadata' => $payload['metadata'] ?? [],
        ];
    }

    public function getCall(string $callId): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Accept' => 'application/json',
        ])->get("{$this->apiUrl}/call/{$callId}");

        if ($response->failed()) {
            throw new \Exception('Call not found');
        }

        return $response->json();
    }

    public function getCalls(array $filters = []): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Accept' => 'application/json',
        ])->get("{$this->apiUrl}/call", $filters);

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }
}