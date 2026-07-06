<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyVapiWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        // Get webhook secret from config
        $secret = config('ai-receptionist.vapi.webhook_secret');

        // If no secret is set, skip verification (for development)
        if (empty($secret)) {
            Log::warning('Vapi webhook secret not set, skipping verification');
            return $next($request);
        }

        // Check signature (if Vapi provides one)
        $signature = $request->header('X-Vapi-Signature');

        if (!$signature) {
            Log::warning('Vapi webhook: No signature provided');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Verify signature
        // Note: You'll need to implement the actual verification based on Vapi's method
        // This is a simple example
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Vapi webhook: Invalid signature');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}