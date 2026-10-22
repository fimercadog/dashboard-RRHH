<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\JobApplicationResource;
use App\Models\Employee;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobApplicationController extends BaseCrudController
{
    protected string $model = JobApplication::class;
    protected string $resource = JobApplicationResource::class;
    protected array $with = [
        'vacancy:id,title,status',
        'candidate:id,first_name,last_name,email,phone',
    ];
    protected array $searchable = ['notes'];
    protected array $filterable = ['status', 'job_vacancy_id', 'job_candidate_id'];
    protected array $immutableOnUpdate = ['job_vacancy_id', 'job_candidate_id'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q   = JobApplication::where('company_id', $cid);
        return response()->json([
            'total'            => $q->clone()->count(),
            'pending'          => $q->clone()->where('status', 'pending')->count(),
            'in_process'       => $q->clone()->where('status', 'in_process')->count(),
            'selected'         => $q->clone()->where('status', 'selected')->count(),
            'rejected'         => $q->clone()->where('status', 'rejected')->count(),
            'hired_this_month' => $q->clone()->where('status', 'selected')->whereNotNull('hired_employee_id')->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count(),
        ]);
    }

    public function hire(Request $request, int $id): JsonResponse
    {
        $application = JobApplication::where('company_id', $this->companyId($request))
            ->with('candidate')
            ->findOrFail($id);

        if ($application->hired_employee_id) {
            return response()->json(['message' => 'El candidato ya fue contratado.'], 422);
        }

        $candidate = $application->candidate;
        $companyId = $this->companyId($request);

        $lastCode = Employee::where('company_id', $companyId)
            ->where('employee_code', 'like', 'EMP-%')
            ->orderByDesc('id')
            ->value('employee_code');

        $nextNum = $lastCode ? ((int) substr($lastCode, 4)) + 1 : 1;
        $code = 'EMP-' . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);

        $employee = Employee::create([
            'company_id'            => $companyId,
            'employee_code'         => $code,
            'first_name'            => $candidate->first_name,
            'last_name'             => $candidate->last_name,
            'identification_type'   => 'CC',
            'identification_number' => $candidate->identification_number ?? '0',
            'email'                 => $candidate->email,
            'phone'                 => $candidate->phone,
            'hire_date'             => now()->toDateString(),
            'employment_status'     => 'active',
        ]);

        $application->update([
            'status'            => 'selected',
            'hired_employee_id' => $employee->id,
        ]);

        return response()->json([
            'message'       => 'Candidato contratado exitosamente.',
            'employee_id'   => $employee->id,
            'employee_code' => $employee->employee_code,
        ]);
    }
}
