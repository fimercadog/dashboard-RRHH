<?php

namespace App\Pending;

use App\Contracts\PendingProvider;
use App\Models\EmployeeDocument;
use App\Models\PermissionRequest;
use App\Models\SickLeave;
use App\Models\User;
use App\Models\VacationRequest;
use Illuminate\Support\Carbon;

class HrPendingProvider implements PendingProvider
{
    public function items(int $companyId, User $user): array
    {
        if (! $user->can('requests.approve') && ! $user->can('documents.manage')) {
            return [];
        }

        $items = [];

        if ($user->can('requests.approve')) {
            $vacCount = VacationRequest::where('company_id', $companyId)
                ->where('status', 'pending')->count();

            $items[] = [
                'type'        => 'vacation_request',
                'title'       => 'Solicitudes de vacaciones',
                'description' => $vacCount === 1 ? '1 solicitud pendiente' : "{$vacCount} solicitudes pendientes",
                'module'      => 'rrhh',
                'route'       => '/app/vacaciones',
                'priority'    => 'high',
                'date'        => VacationRequest::where('company_id', $companyId)->where('status', 'pending')->min('created_at'),
                'count'       => $vacCount,
            ];

            $permCount = PermissionRequest::where('company_id', $companyId)
                ->where('status', 'pending')->count();

            $items[] = [
                'type'        => 'permission_request',
                'title'       => 'Solicitudes de permisos',
                'description' => $permCount === 1 ? '1 permiso pendiente' : "{$permCount} permisos pendientes",
                'module'      => 'rrhh',
                'route'       => '/app/permisos',
                'priority'    => 'high',
                'date'        => PermissionRequest::where('company_id', $companyId)->where('status', 'pending')->min('created_at'),
                'count'       => $permCount,
            ];

            $sickCount = SickLeave::where('company_id', $companyId)
                ->where('status', 'active')->count();

            $items[] = [
                'type'        => 'sick_leave',
                'title'       => 'Incapacidades activas',
                'description' => $sickCount === 1 ? '1 incapacidad sin cierre médico' : "{$sickCount} incapacidades sin cierre médico",
                'module'      => 'rrhh',
                'route'       => '/app/incapacidades',
                'priority'    => 'medium',
                'date'        => SickLeave::where('company_id', $companyId)->where('status', 'active')->min('created_at'),
                'count'       => $sickCount,
            ];
        }

        if ($user->can('documents.manage')) {
            $expiringCount = EmployeeDocument::where('company_id', $companyId)
                ->whereBetween('expiration_date', [Carbon::today(), Carbon::today()->addDays(45)])
                ->count();

            $items[] = [
                'type'        => 'employee_document',
                'title'       => 'Documentos por vencer',
                'description' => $expiringCount === 1 ? '1 documento vence en los próximos 45 días' : "{$expiringCount} documentos vencen en los próximos 45 días",
                'module'      => 'rrhh',
                'route'       => '/app/documentos',
                'priority'    => 'medium',
                'date'        => EmployeeDocument::where('company_id', $companyId)->whereBetween('expiration_date', [Carbon::today(), Carbon::today()->addDays(45)])->min('expiration_date'),
                'count'       => $expiringCount,
            ];
        }

        return $items;
    }
}
