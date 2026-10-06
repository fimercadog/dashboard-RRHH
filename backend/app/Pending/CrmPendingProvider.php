<?php

namespace App\Pending;

use App\Contracts\PendingProvider;
use App\Models\Activity;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class CrmPendingProvider implements PendingProvider
{
    public function items(int $companyId, User $user): array
    {
        if (! $user->can('leads.view') && ! $user->can('deals.manage') && ! $user->can('activities.manage')) {
            return [];
        }

        $items = [];

        if ($user->can('leads.view') && Schema::hasTable('leads')) {
            $newLeads = Lead::where('company_id', $companyId)
                ->where('status', 'new')->count();

            $items[] = [
                'type'        => 'lead_new',
                'title'       => 'Leads sin contactar',
                'description' => $newLeads === 1 ? '1 lead nuevo sin gestionar' : "{$newLeads} leads nuevos sin gestionar",
                'module'      => 'crm',
                'route'       => '/app/leads',
                'priority'    => 'high',
                'date'        => Lead::where('company_id', $companyId)->where('status', 'new')->min('created_at'),
                'count'       => $newLeads,
            ];
        }

        if ($user->can('activities.manage') && Schema::hasTable('activities')) {
            $overdueActivities = Activity::where('company_id', $companyId)
                ->where('status', 'pending')
                ->where('due_at', '<', Carbon::now())
                ->count();

            $items[] = [
                'type'        => 'activity_overdue',
                'title'       => 'Actividades vencidas',
                'description' => $overdueActivities === 1 ? '1 actividad vencida sin completar' : "{$overdueActivities} actividades vencidas sin completar",
                'module'      => 'crm',
                'route'       => '/app/crm/actividades',
                'priority'    => 'high',
                'date'        => Activity::where('company_id', $companyId)->where('status', 'pending')->where('due_at', '<', Carbon::now())->min('due_at'),
                'count'       => $overdueActivities,
            ];
        }

        if ($user->can('deals.manage') && Schema::hasTable('deals')) {
            // Deals stagnant: no activity in 30 days, not won/lost.
            $cutoff = Carbon::now()->subDays(30);
            $stagnantDeals = Deal::where('company_id', $companyId)
                ->whereNotIn('stage', ['won', 'lost'])
                ->where('updated_at', '<', $cutoff)
                ->count();

            $items[] = [
                'type'        => 'deal_stagnant',
                'title'       => 'Oportunidades sin movimiento',
                'description' => $stagnantDeals === 1 ? '1 oportunidad sin actividad en 30 días' : "{$stagnantDeals} oportunidades sin actividad en 30 días",
                'module'      => 'crm',
                'route'       => '/app/crm/deals',
                'priority'    => 'medium',
                'date'        => Deal::where('company_id', $companyId)->whereNotIn('stage', ['won', 'lost'])->where('updated_at', '<', $cutoff)->min('updated_at'),
                'count'       => $stagnantDeals,
            ];
        }

        return $items;
    }
}
