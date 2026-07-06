<?php

namespace App\Contracts\Repositories;

use App\Models\CallLog;

interface CallLogRepositoryInterface
{
    public function findById(int $id): ?CallLog;
    public function findByCallId(string $callId): ?CallLog;
    public function create(array $data): CallLog;
    public function update(CallLog $callLog, array $data): bool;
    public function getForLead(int $leadId): array;
    public function getRecent(int $limit = 50): array;
    public function getInbound(): array;
    public function getOutbound(): array;
    public function getCompleted(): array;
    public function countForLead(int $leadId): int;
}