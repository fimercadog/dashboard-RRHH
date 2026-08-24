<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\PermissionRequest;
use App\Models\SickLeave;
use App\Models\VacationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    use ResolvesCompany;

    public function __invoke(Request $request)
    {
        $companyId = $this->companyId($request);
        $today = Carbon::today();

        return response()->json([
            'metrics' => [
                'total_employees' => Employee::where('company_id', $companyId)->count(),
                'active_employees' => Employee::where('company_id', $companyId)->where('employment_status', 'active')->count(),
                'present_today' => Attendance::where('company_id', $companyId)->whereDate('date', $today)->where('status', 'present')->count(),
                'absent_today' => Attendance::where('company_id', $companyId)->whereDate('date', $today)->where('status', 'absent')->count(),
                'late_today' => Attendance::where('company_id', $companyId)->whereDate('date', $today)->where('status', 'late')->count(),
                'active_sick_leaves' => SickLeave::where('company_id', $companyId)->where('status', 'active')->count(),
                'pending_requests' => VacationRequest::where('company_id', $companyId)->where('status', 'pending')->count()
                    + PermissionRequest::where('company_id', $companyId)->where('status', 'pending')->count(),
                'upcoming_vacations' => VacationRequest::where('company_id', $companyId)->where('status', 'approved')->whereBetween('start_date', [$today, $today->copy()->addDays(30)])->count(),
            ],
            'weekly_attendance' => Attendance::query()
                ->selectRaw('date, status, count(*) as total')
                ->where('company_id', $companyId)
                ->whereBetween('date', [$today->copy()->subDays(6), $today])
                ->groupBy('date', 'status')
                ->orderBy('date')
                ->get(),
            'pending_requests' => [
                'vacations' => VacationRequest::with('employee')->where('company_id', $companyId)->where('status', 'pending')->latest()->limit(5)->get(),
                'permissions' => PermissionRequest::with('employee')->where('company_id', $companyId)->where('status', 'pending')->latest()->limit(5)->get(),
            ],
            'upcoming_events' => [
                'documents' => EmployeeDocument::with('employee')->where('company_id', $companyId)->whereBetween('expiration_date', [$today, $today->copy()->addDays(45)])->orderBy('expiration_date')->limit(6)->get(),
                'birthdays' => Employee::where('company_id', $companyId)->whereNotNull('birth_date')->limit(6)->get(['id', 'first_name', 'last_name', 'birth_date']),
            ],
            'recent_activity' => AuditLog::with('user')->where('company_id', $companyId)->latest()->limit(10)->get(),
        ]);
    }
}
