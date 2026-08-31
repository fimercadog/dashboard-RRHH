<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\PermissionRequest;
use App\Models\Position;
use App\Models\SickLeave;
use App\Models\VacationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    use ResolvesCompany;

    public function __invoke(Request $request)
    {
        $companyId = $this->companyId($request);
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $from30 = $today->copy()->subDays(30);

        $employees = fn () => Employee::where('company_id', $companyId);
        $attendance30 = fn () => Attendance::where('company_id', $companyId)->whereBetween('date', [$from30, $today]);
        $activeEmployees = fn () => ['employees' => fn ($q) => $q->where('employment_status', 'active')];

        return response()->json([
            'generated_at' => now(),
            'headcount' => [
                'total' => $employees()->count(),
                'active' => $employees()->where('employment_status', 'active')->count(),
                'inactive' => $employees()->where('employment_status', '!=', 'active')->count(),
                'hires_month' => $employees()->whereBetween('hire_date', [$monthStart, $today])->count(),
                'terminations_month' => $employees()->whereNotNull('termination_date')->whereBetween('termination_date', [$monthStart, $today])->count(),
                'birthdays_month' => $employees()->whereNotNull('birth_date')->whereMonth('birth_date', $today->month)->count(),
            ],
            'attendance_30d' => [
                'present' => $attendance30()->where('status', 'present')->count(),
                'late' => $attendance30()->where('status', 'late')->count(),
                'absent' => $attendance30()->where('status', 'absent')->count(),
                'late_minutes' => (int) $attendance30()->sum('late_minutes'),
            ],
            'requests' => [
                'vacations_pending' => VacationRequest::where('company_id', $companyId)->where('status', 'pending')->count(),
                'permissions_pending' => PermissionRequest::where('company_id', $companyId)->where('status', 'pending')->count(),
                'sick_leaves_active' => SickLeave::where('company_id', $companyId)->where('status', 'active')->count(),
            ],
            'documents' => [
                'expired' => EmployeeDocument::where('company_id', $companyId)->whereNotNull('expiration_date')->whereDate('expiration_date', '<', $today)->count(),
                'expiring_30d' => EmployeeDocument::where('company_id', $companyId)->whereBetween('expiration_date', [$today, $today->copy()->addDays(30)])->count(),
            ],
            'contracts' => $employees()
                ->where('employment_status', 'active')
                ->selectRaw('contract_type, count(*) as total')
                ->groupBy('contract_type')
                ->pluck('total', 'contract_type')
                ->mapWithKeys(fn ($total, $type) => [($type ?: 'Sin definir') => $total]),
            'by_department' => Department::where('company_id', $companyId)
                ->withCount($activeEmployees())
                ->orderByDesc('employees_count')
                ->get(['id', 'name'])
                ->map(fn ($d) => ['name' => $d->name, 'employees' => $d->employees_count]),
            'by_position' => Position::where('company_id', $companyId)
                ->withCount($activeEmployees())
                ->orderByDesc('employees_count')
                ->get(['id', 'name'])
                ->map(fn ($p) => ['name' => $p->name, 'employees' => $p->employees_count]),
        ]);
    }
}
