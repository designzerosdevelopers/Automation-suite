<?php

namespace App\Repositories;

use App\Contracts\Repositories\LeadRepositoryInterface;
use App\Models\Lead;

class LeadRepository implements LeadRepositoryInterface
{
    public function findById(int $id): ?Lead
    {
        return Lead::find($id);
    }

    public function findByPhone(string $phone): ?Lead
    {
        return Lead::where('phone', $phone)->first();
    }

    public function findByEmail(string $email): ?Lead
    {
        return Lead::where('email', $email)->first();
    }

    public function create(array $data): Lead
    {
        return Lead::create($data);
    }

    public function update(Lead $lead, array $data): bool
    {
        return $lead->update($data);
    }

    public function delete(Lead $lead): bool
    {
        return $lead->delete();
    }

    public function getByStatus(string $status): array
    {
        return Lead::where('status', $status)->get()->toArray();
    }

    public function getRecent(int $limit = 50): array
    {
        return Lead::latest()->limit($limit)->get()->toArray();
    }

    public function incrementCalls(Lead $lead): void
    {
        $lead->increment('total_calls');
    }

    public function updateLastCall(Lead $lead): void
    {
        $lead->update(['last_call_at' => now()]);
    }
}