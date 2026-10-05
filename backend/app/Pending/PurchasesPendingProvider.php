<?php

namespace App\Pending;

use App\Contracts\PendingProvider;
use App\Models\AccountPayable;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class PurchasesPendingProvider implements PendingProvider
{
    public function items(int $companyId, User $user): array
    {
        if (! $user->can('purchases.manage') && ! $user->can('purchases.view')) {
            return [];
        }

        $items = [];

        if ($user->can('purchases.manage') && Schema::hasTable('purchase_orders')) {
            // Orders pending receipt: status not received/cancelled.
            $pendingOrders = PurchaseOrder::where('company_id', $companyId)
                ->whereNotIn('status', ['received', 'cancelled'])
                ->count();

            $items[] = [
                'type'        => 'purchase_order_pending',
                'title'       => 'Órdenes de compra pendientes',
                'description' => $pendingOrders === 1 ? '1 orden sin recepción' : "{$pendingOrders} órdenes sin recepción",
                'module'      => 'compras',
                'route'       => '/app/compras/ordenes',
                'priority'    => 'medium',
                'date'        => PurchaseOrder::where('company_id', $companyId)->whereNotIn('status', ['received', 'cancelled'])->min('created_at'),
                'count'       => $pendingOrders,
            ];
        }

        if ($user->can('purchases.view') && Schema::hasTable('accounts_payable')) {
            // Overdue or due within 7 days.
            $dueSoon = AccountPayable::where('company_id', $companyId)
                ->where('status', '!=', 'paid')
                ->where('due_date', '<=', Carbon::today()->addDays(7))
                ->count();

            $items[] = [
                'type'        => 'account_payable_due',
                'title'       => 'Cuentas por pagar próximas',
                'description' => $dueSoon === 1 ? '1 CxP vence en los próximos 7 días' : "{$dueSoon} CxP vencen en los próximos 7 días",
                'module'      => 'compras',
                'route'       => '/app/compras/cxp',
                'priority'    => 'high',
                'date'        => AccountPayable::where('company_id', $companyId)->where('status', '!=', 'paid')->where('due_date', '<=', Carbon::today()->addDays(7))->min('due_date'),
                'count'       => $dueSoon,
            ];
        }

        return $items;
    }
}
