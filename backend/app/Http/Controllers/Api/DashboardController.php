<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\PermissionRequest;
use App\Models\SickLeave;
use App\Models\VacationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    use ResolvesCompany;

    public function __invoke(Request $request)
    {
        $companyId = $this->companyId($request);
        $today = Carbon::today();
        $endOfToday = $today->copy()->endOfDay();
        // Ventanas moviles de 30 dias para los deltas "vs periodo anterior":
        // siempre tienen datos, a diferencia del mes calendario recien empezado.
        $win1Start = $today->copy()->subDays(30);
        $win2Start = $today->copy()->subDays(60);
        $rangeStart = $today->copy()->subMonths(11)->startOfMonth();

        $presentToday = Attendance::where('company_id', $companyId)->whereDate('date', $today)->where('status', 'present')->count();
        $lateToday = Attendance::where('company_id', $companyId)->whereDate('date', $today)->where('status', 'late')->count();
        $absentToday = Attendance::where('company_id', $companyId)->whereDate('date', $today)->where('status', 'absent')->count();
        $activeEmployees = Employee::where('company_id', $companyId)->where('employment_status', 'active')->count();

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'metrics' => [
                'total_employees' => Employee::where('company_id', $companyId)->count(),
                'active_employees' => $activeEmployees,
                'present_today' => $presentToday,
                'absent_today' => $absentToday,
                'late_today' => $lateToday,
                'active_sick_leaves' => SickLeave::where('company_id', $companyId)->where('status', 'active')->count(),
                'pending_requests' => VacationRequest::where('company_id', $companyId)->where('status', 'pending')->count()
                    + PermissionRequest::where('company_id', $companyId)->where('status', 'pending')->count(),
                'upcoming_vacations' => VacationRequest::where('company_id', $companyId)->where('status', 'approved')->whereBetween('start_date', [$today, $today->copy()->addDays(30)])->count(),
            ],
            'deltas' => [
                'hires' => $this->delta(
                    Employee::where('company_id', $companyId)->whereBetween('hire_date', [$win1Start, $endOfToday])->count(),
                    Employee::where('company_id', $companyId)->whereBetween('hire_date', [$win2Start, $win1Start])->count(),
                ),
                'requests' => $this->delta(
                    $this->requestsBetween($companyId, $win1Start, $endOfToday),
                    $this->requestsBetween($companyId, $win2Start, $win1Start),
                ),
                'attendance_rate' => $this->delta(
                    $this->attendanceRate($companyId, $win1Start, $endOfToday),
                    $this->attendanceRate($companyId, $win2Start, $win1Start),
                ),
            ],
            'attendance_funnel' => [
                ['stage' => 'Plantilla activa', 'count' => $activeEmployees],
                ['stage' => 'Con marca de entrada', 'count' => $presentToday + $lateToday],
                ['stage' => 'A tiempo', 'count' => $presentToday],
            ],
            'weekly_attendance' => Attendance::query()
                ->selectRaw('date, status, count(*) as total')
                ->where('company_id', $companyId)
                ->whereBetween('date', [$today->copy()->subDays(6)->toDateString(), $today->toDateString()])
                ->groupBy('date', 'status')
                ->orderBy('date')
                ->get(),
            'headcount_by_department' => Employee::query()
                ->selectRaw('coalesce(departments.name, ?) as department, count(*) as total', ['Sin area'])
                ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
                ->where('employees.company_id', $companyId)
                ->groupBy('department')
                ->orderByDesc('total')
                ->get(),
            'headcount_by_status' => Employee::query()
                ->selectRaw('employment_status as status, count(*) as total')
                ->where('company_id', $companyId)
                ->groupBy('employment_status')
                ->orderByDesc('total')
                ->get(),
            'trends' => [
                'attendance_monthly' => $this->attendanceMonthly($companyId, $rangeStart),
                'headcount_flow' => $this->headcountFlow($companyId, $rangeStart),
                'requests_monthly' => $this->requestsMonthly($companyId, $rangeStart),
            ],
            'pending_requests' => [
                'vacations' => VacationRequest::with('employee')->where('company_id', $companyId)->where('status', 'pending')->latest()->limit(5)->get(),
                'permissions' => PermissionRequest::with('employee')->where('company_id', $companyId)->where('status', 'pending')->latest()->limit(5)->get(),
            ],
            'upcoming_events' => [
                'documents' => EmployeeDocument::with('employee')->where('company_id', $companyId)->whereBetween('expiration_date', [$today, $today->copy()->addDays(45)])->orderBy('expiration_date')->limit(6)->get(),
                'birthdays' => Employee::where('company_id', $companyId)->where('employment_status', '!=', 'terminated')->whereNotNull('birth_date')->get(['id', 'first_name', 'last_name', 'birth_date'])
                    ->sortBy(function ($e) use ($today) {
                        $next = Carbon::parse($e->birth_date)->setYear($today->year)->startOfDay();
                        if ($next->lt($today)) {
                            $next->addYear();
                        }

                        return $today->diffInDays($next);
                    })
                    ->take(5)
                    ->values(),
            ],
            'recent_activity' => AuditLog::with('user:id,name')->where('company_id', $companyId)->latest()->limit(8)->get()
                ->map(fn (AuditLog $log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'module' => $log->module,
                    'user' => $log->user?->name,
                    'created_at' => $log->created_at,
                ]),
        ]);
    }

    /** @return array{current: float|int, previous: float|int, pct: float|null} */
    private function delta(float|int $current, float|int $previous): array
    {
        return [
            'current' => $current,
            'previous' => $previous,
            'pct' => $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : null,
        ];
    }

    private function requestsBetween(int $companyId, Carbon $from, Carbon $to): int
    {
        return VacationRequest::where('company_id', $companyId)->whereBetween('start_date', [$from, $to])->count()
            + PermissionRequest::where('company_id', $companyId)->whereBetween('start_date', [$from, $to])->count()
            + SickLeave::where('company_id', $companyId)->whereBetween('start_date', [$from, $to])->count();
    }

    private function attendanceRate(int $companyId, Carbon $from, Carbon $to): float
    {
        $range = [$from->toDateString(), $to->toDateString()];
        $total = Attendance::where('company_id', $companyId)->whereBetween('date', $range)->count();
        if ($total === 0) {
            return 0.0;
        }
        $onTime = Attendance::where('company_id', $companyId)->whereBetween('date', $range)->whereIn('status', ['present', 'late'])->count();

        return round(($onTime / $total) * 100, 1);
    }

    /** @return list<string> Etiquetas "Y-m" de los ultimos 12 meses, mas antiguo primero. */
    private function monthKeys(): array
    {
        return collect(range(11, 0))->map(fn ($i) => Carbon::today()->subMonths($i)->format('Y-m'))->all();
    }

    private function attendanceMonthly(int $companyId, Carbon $from): Collection
    {
        $rows = Attendance::where('company_id', $companyId)
            ->where('date', '>=', $from->toDateString())
            ->selectRaw("strftime('%Y-%m', date) as month, status, count(*) as total")
            ->groupBy('month', 'status')
            ->get();

        $currentMonth = Carbon::today()->format('Y-m');

        return collect($this->monthKeys())->map(function (string $month) use ($rows, $currentMonth) {
            $forMonth = $rows->where('month', $month);
            $present = (int) $forMonth->where('status', 'present')->sum('total');
            $late = (int) $forMonth->where('status', 'late')->sum('total');
            $absent = (int) $forMonth->where('status', 'absent')->sum('total');
            $total = $present + $late + $absent;

            return [
                'month' => $month,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'rate' => $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0,
                'partial' => $month === $currentMonth,
            ];
        });
    }

    private function headcountFlow(int $companyId, Carbon $from): Collection
    {
        $hires = Employee::where('company_id', $companyId)->where('hire_date', '>=', $from)
            ->selectRaw("strftime('%Y-%m', hire_date) as month, count(*) as total")
            ->groupBy('month')->pluck('total', 'month');
        $terms = Employee::where('company_id', $companyId)->whereNotNull('termination_date')->where('termination_date', '>=', $from)
            ->selectRaw("strftime('%Y-%m', termination_date) as month, count(*) as total")
            ->groupBy('month')->pluck('total', 'month');

        return collect($this->monthKeys())->map(fn (string $month) => [
            'month' => $month,
            'hires' => (int) ($hires[$month] ?? 0),
            'terminations' => (int) ($terms[$month] ?? 0),
        ]);
    }

    private function requestsMonthly(int $companyId, Carbon $from): Collection
    {
        $count = fn (string $model) => $model::where('company_id', $companyId)->where('start_date', '>=', $from)
            ->selectRaw("strftime('%Y-%m', start_date) as month, count(*) as total")
            ->groupBy('month')->pluck('total', 'month');

        $vacations = $count(VacationRequest::class);
        $permissions = $count(PermissionRequest::class);
        $sick = $count(SickLeave::class);

        return collect($this->monthKeys())->map(fn (string $month) => [
            'month' => $month,
            'vacations' => (int) ($vacations[$month] ?? 0),
            'permissions' => (int) ($permissions[$month] ?? 0),
            'sick_leaves' => (int) ($sick[$month] ?? 0),
        ]);
    }
}
