<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CallLogController extends Controller
{
    /**
     * Display a listing of call logs from Vapi.
     */
    public function index(Request $request)
    {
        // Check if user is super admin
        if (auth()->id() !== 1) {
            return redirect()->route('dashboard')
                ->with('error', 'Unauthorized access. Only Super Admin can view this page.');
        }

        // Get API key from config
        $apiKey = config('ai-receptionist.vapi.api_key');
        
        if (!$apiKey) {
            return view('admin.call-logs', [
                'calls' => [],
                'error' => 'Vapi API key is not configured. Please add VAPI_API_KEY to your .env file.'
            ]);
        }

        // Fetch calls from Vapi API
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Accept' => 'application/json',
        ])->get('https://api.vapi.ai/call', [
            'limit' => 100,
        ]);

        $calls = [];
        $error = null;

        if ($response->successful()) {
            $callsData = $response->json();
            
            if (isset($callsData['results'])) {
                $calls = $callsData['results'];
            } else if (is_array($callsData)) {
                $calls = $callsData;
            } else {
                $calls = [];
            }
        } else {
            $error = 'Failed to fetch calls from Vapi API. Status: ' . $response->status() . ' - ' . $response->body();
        }

        return view('admin.call-logs', compact('calls', 'error'));
    }

    /**
     * Get transcript for a specific call
     */
    public function transcript($callId)
    {
        // Check if user is super admin
        if (auth()->id() !== 1) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $apiKey = config('ai-receptionist.vapi.api_key');
        
        if (!$apiKey) {
            return response()->json(['error' => 'Vapi API key is not configured'], 500);
        }

        // Fetch call details
        $callResponse = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Accept' => 'application/json',
        ])->get("https://api.vapi.ai/call/{$callId}");

        if (!$callResponse->successful()) {
            return response()->json(['error' => 'Call not found or API error: ' . $callResponse->status()], 404);
        }

        $call = $callResponse->json();
        
        // Extract transcript - Vapi returns it in multiple formats
        $transcript = $this->extractTranscript($call);
        $summary = $this->extractSummary($call);
        $messages = $this->extractMessages($call);

        return response()->json([
            'call' => $call,
            'transcript' => $transcript,
            'summary' => $summary,
            'messages' => $messages,
        ]);
    }

    /**
     * Extract transcript from call data
     * Vapi returns transcript as a string with "AI:" and "User:" prefixes
     */
    private function extractTranscript($call)
    {
        // Check for transcript string
        if (isset($call['transcript']) && is_string($call['transcript']) && !empty($call['transcript'])) {
            return $this->parseTranscriptString($call['transcript']);
        }
        
        // Check for messages array
        if (isset($call['messages']) && is_array($call['messages']) && count($call['messages']) > 0) {
            return $this->extractMessages($call);
        }
        
        // Check for artifact transcript
        if (isset($call['artifact']['transcript'])) {
            if (is_string($call['artifact']['transcript'])) {
                return $this->parseTranscriptString($call['artifact']['transcript']);
            }
            return $call['artifact']['transcript'];
        }
        
        return [];
    }

    /**
     * Parse transcript string with "AI:" and "User:" prefixes
     */
    private function parseTranscriptString($transcriptString)
    {
        $messages = [];
        $lines = explode("\n", $transcriptString);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Check for AI: prefix
            if (strpos($line, 'AI:') === 0) {
                $messages[] = [
                    'role' => 'assistant',
                    'type' => 'bot',
                    'message' => trim(substr($line, 3)),
                    'timestamp' => null,
                ];
            }
            // Check for User: prefix
            else if (strpos($line, 'User:') === 0) {
                $messages[] = [
                    'role' => 'user',
                    'type' => 'user',
                    'message' => trim(substr($line, 5)),
                    'timestamp' => null,
                ];
            }
            // Check for Bot: prefix
            else if (strpos($line, 'Bot:') === 0) {
                $messages[] = [
                    'role' => 'assistant',
                    'type' => 'bot',
                    'message' => trim(substr($line, 4)),
                    'timestamp' => null,
                ];
            }
        }
        
        return $messages;
    }

    /**
     * Extract messages from the messages array
     */
    private function extractMessages($call)
    {
        if (!isset($call['messages']) || !is_array($call['messages'])) {
            return [];
        }
        
        $messages = [];
        foreach ($call['messages'] as $msg) {
            // Skip system messages as they are usually instructions
            if (isset($msg['role']) && $msg['role'] === 'system') {
                continue;
            }
            
            $messages[] = [
                'role' => $msg['role'] ?? 'unknown',
                'type' => $msg['role'] ?? 'unknown',
                'message' => $msg['message'] ?? $msg['content'] ?? '',
                'timestamp' => isset($msg['time']) ? date('Y-m-d H:i:s', $msg['time'] / 1000) : null,
                'duration' => $msg['duration'] ?? null,
                'secondsFromStart' => $msg['secondsFromStart'] ?? null,
            ];
        }
        
        return $messages;
    }

    /**
     * Extract summary from call data
     */
    private function extractSummary($call)
    {
        if (isset($call['summary']) && !empty($call['summary'])) {
            return $call['summary'];
        }
        
        if (isset($call['artifact']['summary']) && !empty($call['artifact']['summary'])) {
            return $call['artifact']['summary'];
        }
        
        if (isset($call['analysis']['summary']) && !empty($call['analysis']['summary'])) {
            return $call['analysis']['summary'];
        }
        
        return null;
    }

    /**
     * Get call details
     */
    public function show($callId)
    {
        // Check if user is super admin
        if (auth()->id() !== 1) {
            return redirect()->route('dashboard')
                ->with('error', 'Unauthorized access.');
        }

        $apiKey = config('ai-receptionist.vapi.api_key');
        
        // Fetch call details
        $callResponse = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Accept' => 'application/json',
        ])->get("https://api.vapi.ai/call/{$callId}");

        if (!$callResponse->successful()) {
            return back()->with('error', 'Call not found.');
        }

        $call = $callResponse->json();
        
        // Extract transcript and summary
        $transcript = $this->extractTranscript($call);
        $summary = $this->extractSummary($call);

        return view('admin.call-logs-show', compact('call', 'transcript', 'summary'));
    }
}