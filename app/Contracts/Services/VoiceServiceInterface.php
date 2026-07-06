<?php

namespace App\Contracts\Services;

interface VoiceServiceInterface
{
    /**
     * Make an outbound call
     */
    public function makeCall(string $phone, string $name, ?string $message = null): array;

    /**
     * Handle incoming call webhook
     */
    public function handleWebhook(array $payload): array;

    /**
     * Get call details by ID
     */
    public function getCall(string $callId): array;

    /**
     * Get call history
     */
    public function getCalls(array $filters = []): array;
}