<?php

namespace App\Contracts;

use App\Models\User;

interface PendingProvider
{
    /**
     * Return pending items for the given company and user.
     * Each provider must filter by company_id and only return items
     * the user has permission to see.
     *
     * @return array<int, array{
     *   type: string,
     *   title: string,
     *   description: string,
     *   module: string,
     *   route: string,
     *   priority: string,
     *   date: string|null,
     *   count: int
     * }>
     */
    public function items(int $companyId, User $user): array;
}
