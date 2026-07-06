<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Repositories\CallLogRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VapiCallController extends Controller
{
    protected CallLogRepository $callLogRepository;

    public function __construct(CallLogRepository $callLogRepository)
    {
        $this->callLogRepository = $callLogRepository;
    }

    public function callHistory()
    {
        if (auth()->id() !== 1) {
            return view('callhistory', ['calls' => [], 'error' => 'Unauthorized access. Only Super Admin can view this page.']);
        }

        $apiKey = config('ai-receptionist.vapi.api_key');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Accept' => 'application/json',
        ])->get('https://api.vapi.ai/call', ['limit' => 100]);

        $calls = [];

        if ($response->successful()) {
            $calls = $response->json();
        }

        return view('callhistory', compact('calls'));
    }

    public function handleCall(Request $request)
    {
        if (!auth()->user()->is_approved) {
            return back()->with('error', 'Your account is waiting for admin approval.');
        }

        $request->validate([
            'contact_number' => 'required|string',
            'name' => 'required|string',
        ]);

        $apiKey = config('ai-receptionist.vapi.api_key');
        $assistantId = config('ai-receptionist.vapi.assistant_id');
        $phoneNumberId = config('ai-receptionist.vapi.phone_number_id');

        $name = trim($request->name);
        $number = trim($request->contact_number);
        if (substr($number, 0, 1) !== '+') {
            $number = '+' . $number;
        }

        $payload = [
            "assistantId" => $assistantId,
            "phoneNumberId" => $phoneNumberId,
            "customer" => ["number" => $number, "name" => $name],
        ];

        $response = Http::withHeaders([
            "Authorization" => "Bearer {$apiKey}",
            "Content-Type" => "application/json",
        ])->post("https://api.vapi.ai/call", $payload);

        if ($response->failed()) {
            return back()->with('error', 'Call failed: ' . $response->body());
        }

        $callData = $response->json();

        $this->callLogRepository->create([
            'call_id' => $callData['id'] ?? null,
            'direction' => 'outbound',
            'status' => 'initiated',
            'duration' => 0,
            'metadata' => [
                'customer_name' => $name,
                'customer_number' => $number,
                'initiated_by' => auth()->user()->name,
                'initiated_at' => now()->toDateTimeString(),
            ],
        ]);

        return back()->with('success', "📞 Call initiated to {$name} ({$number})!");
    }
}