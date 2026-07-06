<?php

namespace App\Contracts\Services;

interface LLMServiceInterface
{
    /**
     * Generate a response based on user message
     */
    public function generateResponse(string $message, array $context = []): string;

    /**
     * Classify the intent of a message
     */
    public function classifyIntent(string $message): string;

    /**
     * Extract booking information from message
     */
    public function extractBookingInfo(string $message): array;

    /**
     * Generate a summary of conversation
     */
    public function generateSummary(array $transcript): string;
}