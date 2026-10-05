<?php

namespace App\Pending;

use App\Contracts\PendingProvider;
use App\Models\CashAccount;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class FinancePendingProvider implements PendingProvider
{
    public function items(int $companyId, User $user): array
    {
        if (! $user->can('finance.manage') && ! $user->can('finance.view')) {
            return [];
        }

        $items = [];

        if (Schema::hasTable('cash_accounts')) {
            // Cash accounts with negative balance — Finance-specific concern.
            $overdraft = CashAccount::where('company_id', $companyId)
                ->where('balance', '<', 0)
                ->count();

            $items[] = [
                'type'        => 'cash_overdraft',
                'title'       => 'Cuentas con saldo negativo',
                'description' => $overdraft === 1 ? '1 cuenta de caja con saldo negativo' : "{$overdraft} cuentas de caja con saldo negativo",
                'module'      => 'finanzas',
                'route'       => '/app/finanzas/cuentas',
                'priority'    => 'high',
                'date'        => null,
                'count'       => $overdraft,
            ];
        }

        return $items;
    }
}
