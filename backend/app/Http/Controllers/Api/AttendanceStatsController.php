<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceStatsController extends Controller
{
    use ResolvesCompany;

    private const ALL_TRACKED = ['permission', 'vacation', 'sick_leave', 'absent', 'late'];

    public function __invoke(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'from'          => ['required', 'date', 'date_format:Y-m-d'],
            'to'            => [
                'required',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:from',
                function ($attribute, $value, $fail) use ($request) {
                    $from = $request->input('from');
                    if (! $from || ! strtotime($from)) {
                        return;
                    }
                    $maxTo = \Carbon\Carbon::parse($from)->addDays(365)->toDateString();
                    if ($value > $maxTo) {
                        $fail('El rango máximo permitido es de 366 días.');
                    }
                },
            ],
            'department_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'employee_id'   => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        $from = $validated['from'];
        $to   = $validated['to'];

        $byEmployee = Attendance::query()
            ->selectRaw('
                attendances.employee_id,
                MIN(employees.first_name) as first_name,
                MIN(employees.last_name)  as last_name,
                MIN(COALESCE(departments.name, \'Sin área\')) as department,
                SUM(CASE WHEN attendances.status IN (\'permission\',\'vacation\',\'sick_leave\') THEN 1 ELSE 0 END) as absences_justified,
                SUM(CASE WHEN attendances.status = \'absent\'  THEN 1 ELSE 0 END) as absences_unjustified,
                SUM(CASE WHEN attendances.status = \'late\'    THEN 1 ELSE 0 END) as tardiness_count,
                SUM(CASE WHEN attendances.status = \'late\'    THEN attendances.late_minutes ELSE 0 END) as tardiness_minutes
            ')
            ->join('employees', function ($join) use ($companyId) {
                $join->on('employees.id', '=', 'attendances.employee_id')
                     ->where('employees.company_id', $companyId);
            })
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->where('attendances.company_id', $companyId)
            ->whereBetween('attendances.date', [$from, $to])
            ->whereIn('attendances.status', self::ALL_TRACKED)
            ->when(
                ! empty($validated['department_id']),
                fn ($q) => $q->where('employees.department_id', $validated['department_id'])
            )
            ->when(
                ! empty($validated['employee_id']),
                fn ($q) => $q->where('attendances.employee_id', $validated['employee_id'])
            )
            ->groupBy('attendances.employee_id')
            ->orderByRaw('MIN(employees.first_name)')
            ->get()
            ->map(fn ($row) => [
                'employee_id'           => $row->employee_id,
                'full_name'             => trim($row->first_name . ' ' . $row->last_name),
                'department'            => $row->department,
                'absences_justified'    => (int) $row->absences_justified,
                'absences_unjustified'  => (int) $row->absences_unjustified,
                'tardiness_count'       => (int) $row->tardiness_count,
                'tardiness_minutes'     => (int) $row->tardiness_minutes,
            ]);

        // Summary derived from by_employee to avoid a second DB trip
        $summary = [
            'absences' => [
                'justified'   => $byEmployee->sum('absences_justified'),
                'unjustified' => $byEmployee->sum('absences_unjustified'),
            ],
            'tardiness' => [
                'count'         => $byEmployee->sum('tardiness_count'),
                'total_minutes' => $byEmployee->sum('tardiness_minutes'),
            ],
        ];

        // by_department aggregated from by_employee in PHP (no extra query)
        $byDepartment = $byEmployee
            ->groupBy('department')
            ->map(fn ($deptRows, $dept) => [
                'department'            => $dept,
                'absences_justified'    => $deptRows->sum('absences_justified'),
                'absences_unjustified'  => $deptRows->sum('absences_unjustified'),
                'tardiness_count'       => $deptRows->sum('tardiness_count'),
                'tardiness_minutes'     => $deptRows->sum('tardiness_minutes'),
            ])
            ->sortBy('department')
            ->values();

        return response()->json([
            'summary'       => $summary,
            'by_employee'   => $byEmployee->values(),
            'by_department' => $byDepartment,
            'meta' => [
                'from'               => $from,
                'to'                 => $to,
                'classification_note'=> 'Solo se contabilizan registros explícitos. Justificadas: permiso, vacaciones, incapacidad. No justificadas: ausente.',
            ],
        ]);
    }
}
