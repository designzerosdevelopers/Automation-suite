<?php

namespace App\Repositories;

use App\Models\CallLog;

class CallLogRepository
{
    public function create(array $data): CallLog
    {
        return CallLog::create($data);
    }

    public function update(CallLog $callLog, array $data): CallLog
    {
        $callLog->update($data);
        return $callLog;
    }

    public function findById(int $id): ?CallLog
    {
        return CallLog::find($id);
    }

    public function findByCallId(string $callId): ?CallLog
    {
        return CallLog::where('call_id', $callId)->first();
    }

    public function getRecent(int $limit = 10)
    {
        return CallLog::latest()
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getAll()
    {
        return CallLog::latest()->paginate(50);
    }

    public function updateStatus(CallLog $callLog, string $status): CallLog
    {
        $callLog->update(['status' => $status]);
        return $callLog;
    }

    public function updateTranscript(CallLog $callLog, array $transcript): CallLog
    {
        $callLog->update(['transcript' => $transcript]);
        return $callLog;
    }

    public function delete(CallLog $callLog): bool
    {
        return $callLog->delete();
    }

    public function getWithTranscript(int $id): ?CallLog
    {
        return CallLog::find($id);
    }

    public function getByIntent(string $intent, int $limit = 10)
    {
        return CallLog::where('intent', $intent)
            ->latest()
            ->limit($limit)
            ->get();
    }
}