<?php

namespace App\Pending;

use App\Contracts\PendingProvider;
use App\Models\AccountsReceivable;
use App\Models\Quote;
use App\Models\SaleOrder;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class SalesPendingProvider implements PendingProvider
{
    public function items(int $companyId, User $user): array
    {
        if (! $user->can('sales.manage') && ! $user->can('sales.view')) {
            return [];
        }

        $items = [];

        if ($user->can('sales.manage') && Schema::hasTable('quotes')) {
            $pendingQuotes = Quote::where('company_id', $companyId)
                ->whereIn('status', ['draft', 'sent'])
                ->count();

            $items[] = [
                'type'        => 'quote_pending',
                'title'       => 'Cotizaciones pendientes',
                'description' => $pendingQuotes === 1 ? '1 cotización sin respuesta' : "{$pendingQuotes} cotizaciones sin respuesta",
                'module'      => 'ventas',
                'route'       => '/app/ventas/cotizaciones',
                'priority'    => 'medium',
                'date'        => Quote::where('company_id', $companyId)->whereIn('status', ['draft', 'sent'])->min('created_at'),
                'count'       => $pendingQuotes,
            ];
        }

        if ($user->can('sales.manage') && Schema::hasTable('sale_orders')) {
            $unInvoiced = SaleOrder::where('company_id', $companyId)
                ->where('status', 'confirmed')
                ->count();

            $items[] = [
                'type'        => 'sale_order_uninvoiced',
                'title'       => 'Pedidos por facturar',
                'description' => $unInvoiced === 1 ? '1 pedido confirmado sin factura' : "{$unInvoiced} pedidos confirmados sin factura",
                'module'      => 'ventas',
                'route'       => '/app/ventas/pedidos',
                'priority'    => 'high',
                'date'        => SaleOrder::where('company_id', $companyId)->where('status', 'confirmed')->min('created_at'),
                'count'       => $unInvoiced,
            ];
        }

        if ($user->can('sales.view') && Schema::hasTable('accounts_receivable')) {
            $overdue = AccountsReceivable::where('company_id', $companyId)
                ->where('status', '!=', 'paid')
                ->where('due_date', '<=', Carbon::today()->addDays(7))
                ->count();

            $items[] = [
                'type'        => 'account_receivable_due',
                'title'       => 'Cuentas por cobrar próximas',
                'description' => $overdue === 1 ? '1 CxC vence en los próximos 7 días' : "{$overdue} CxC vencen en los próximos 7 días",
                'module'      => 'ventas',
                'route'       => '/app/ventas/cxc',
                'priority'    => 'high',
                'date'        => AccountsReceivable::where('company_id', $companyId)->where('status', '!=', 'paid')->where('due_date', '<=', Carbon::today()->addDays(7))->min('due_date'),
                'count'       => $overdue,
            ];
        }

        return $items;
    }
}
