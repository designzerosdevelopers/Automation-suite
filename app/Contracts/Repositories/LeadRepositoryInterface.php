<?php

namespace App\Contracts\Repositories;

use App\Models\Lead;

interface LeadRepositoryInterface
{
    public function findById(int $id): ?Lead;
    public function findByPhone(string $phone): ?Lead;
    public function findByEmail(string $email): ?Lead;
    public function create(array $data): Lead;
    public function update(Lead $lead, array $data): bool;
    public function delete(Lead $lead): bool;
    public function getByStatus(string $status): array;
    public function getRecent(int $limit = 50): array;
    public function incrementCalls(Lead $lead): void;
    public function updateLastCall(Lead $lead): void;
}