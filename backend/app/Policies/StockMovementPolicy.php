<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('inventory.view') || $user->hasPermissionTo('inventory.movements');
    }

    public function view(User $user, StockMovement $movement): bool
    {
        return $movement->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('inventory.movements');
    }

    public function update(User $user, StockMovement $movement): bool
    {
        // Stock movements are immutable once created; reversals go through StockService
        return false;
    }

    public function delete(User $user, StockMovement $movement): bool
    {
        return false;
    }
}
