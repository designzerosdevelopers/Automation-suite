<?php

namespace App\Services\LLM;

use App\Contracts\Services\LLMServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService implements LLMServiceInterface
{
    protected string $apiKey;
    protected string $model;
    protected float $temperature;

    public function __construct()
    {
        $this->apiKey = config('ai-receptionist.gemini.api_key');
        $this->model = config('ai-receptionist.gemini.model', 'gemini-1.5-pro');
        $this->temperature = config('ai-receptionist.gemini.temperature', 0.3);
    }

    public function generateResponse(string $message, array $context = []): string
    {
        $systemPrompt = $this->getSystemPrompt($context);

        $response = Http::post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}",
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $systemPrompt],
                            ['text' => $message],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => $this->temperature,
                ],
            ]
        );

        if ($response->failed()) {
            Log::error('Gemini API failed', ['error' => $response->body()]);
            return "I'm having trouble processing your request. Please try again or call us directly.";
        }

        $data = $response->json();
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? "I couldn't generate a response. Please try again.";
    }

    public function classifyIntent(string $message): string
    {
        $prompt = "Classify the following message intent. Return ONLY one word: booking, faq, escalation, or other.\n\nMessage: {$message}";

        $response = Http::post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}",
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 10,
                ],
            ]
        );

        if ($response->failed()) {
            return 'other';
        }

        $data = $response->json();
        $intent = trim(strtolower($data['candidates'][0]['content']['parts'][0]['text'] ?? 'other'));

        return in_array($intent, ['booking', 'faq', 'escalation']) ? $intent : 'other';
    }

    public function extractBookingInfo(string $message): array
    {
        $prompt = "Extract booking information from this message. Return as JSON with these fields: date, time, service, name, phone, email. If not present, leave blank.\n\nMessage: {$message}";

        $response = Http::post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}",
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 200,
                ],
            ]
        );

        if ($response->failed()) {
            return [];
        }

        $data = $response->json();
        $result = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

        return json_decode($result, true) ?? [];
    }

    public function generateSummary(array $transcript): string
    {
        $transcriptText = json_encode($transcript);

        $prompt = "Summarize this conversation in 1-2 sentences. Keep it brief.\n\nConversation: {$transcriptText}";

        $response = Http::post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}",
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'maxOutputTokens' => 100,
                ],
            ]
        );

        if ($response->failed()) {
            return '';
        }

        $data = $response->json();
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    protected function getSystemPrompt(array $context): string
    {
        $clinic = config('ai-receptionist.clinic');
        $services = implode(', ', $clinic['services'] ?? []);
        $faqs = $context['faqs'] ?? $this->getDefaultFaqs();

        return "You are AI Receptionist for {$clinic['name']}.

Clinic Info:
- Address: {$clinic['address']}
- Phone: {$clinic['phone']}
- Services: {$services}

FAQs:
" . json_encode($faqs, JSON_PRETTY_PRINT) . "

Guidelines:
1. Be friendly, professional, and helpful
2. If asked to book, confirm date/time/service
3. For medical questions, say: 'I'll connect you with our team'
4. Keep responses concise and clear
5. If you don't know something, offer to connect with staff

Current context: " . json_encode($context);
    }

    protected function getDefaultFaqs(): array
    {
        return [
            ['question' => 'What services do you offer?', 'answer' => 'We offer Botox, Fillers, Laser treatments, and consultations.'],
            ['question' => 'What are your hours?', 'answer' => 'We are open Monday to Saturday, 9AM to 8PM.'],
            ['question' => 'How much does Botox cost?', 'answer' => 'Botox starts from $200 per area. Price may vary based on treatment.'],
        ];
    }
}