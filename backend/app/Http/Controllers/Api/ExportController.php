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
use App\Services\TableQueryService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
    use ResolvesCompany;

    private array $map = [
        'employees' => [Employee::class, ['employee_code', 'first_name', 'last_name', 'email', 'employment_status']],
        'attendances' => [Attendance::class, ['employee_id', 'date', 'status', 'check_in', 'check_out', 'late_minutes']],
        'vacation-requests' => [VacationRequest::class, ['employee_id', 'start_date', 'end_date', 'requested_days', 'status']],
        'permission-requests' => [PermissionRequest::class, ['employee_id', 'type', 'start_date', 'end_date', 'status']],
        'sick-leaves' => [SickLeave::class, ['employee_id', 'start_date', 'end_date', 'days', 'type', 'status']],
        'employee-documents' => [EmployeeDocument::class, ['employee_id', 'document_type', 'name', 'expiration_date', 'status']],
        'audit-logs' => [AuditLog::class, ['user_id', 'action', 'module', 'entity', 'entity_id', 'created_at']],
    ];

    private array $permissionByResource = [
        'employees' => 'employees.manage',
        'attendances' => 'attendance.manage',
        'vacation-requests' => 'requests.approve',
        'permission-requests' => 'requests.approve',
        'sick-leaves' => 'requests.approve',
        'employee-documents' => 'documents.manage',
        'audit-logs' => 'audit.view',
    ];

    public function __invoke(Request $request, string $resource, string $format, TableQueryService $tables)
    {
        abort_unless($request->user()->can($this->permissionByResource[$resource]), 403);

        [$model, $columns] = $this->map[$resource];
        $query = $model::query()->where('company_id', $this->companyId($request));
        $tables->apply($request, $query, $columns, ['status' => 'status']);
        $rows = $query->limit(5000)->get($columns);

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.table', compact('resource', 'columns', 'rows'));
            return $pdf->download($resource.'.pdf');
        }

        $callback = function () use ($columns, $rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);
            foreach ($rows as $row) {
                fputcsv($handle, collect($columns)->map(fn ($column) => $row->{$column})->all());
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, $resource.'.csv', ['Content-Type' => 'text/csv']);
    }
}
