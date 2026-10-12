<?php

namespace App\Services\Import\Handlers;

use App\Contracts\ImportableEntity;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmployeeImportHandler implements ImportableEntity
{
    public function modelClass(): string
    {
        return Employee::class;
    }

    public function requiredColumns(): array
    {
        return ['employee_code', 'first_name', 'last_name', 'identification_type', 'identification_number', 'hire_date', 'employment_status'];
    }

    public function optionalColumns(): array
    {
        return ['email', 'birth_date', 'termination_date', 'salary', 'position_id', 'department_id'];
    }

    public function duplicateKeys(): array
    {
        return ['employee_code', 'identification_number'];
    }

    public function validateRow(array $row, int $rowNumber, int $companyId): array
    {
        $rules = [
            'employee_code'         => ['required', 'string', 'max:50'],
            'first_name'            => ['required', 'string', 'max:80'],
            'last_name'             => ['required', 'string', 'max:80'],
            'identification_type'   => ['required', 'string', 'max:20'],
            'identification_number' => ['required', 'string', 'max:30'],
            'hire_date'             => ['required', 'date'],
            'employment_status'     => ['required', 'in:active,inactive,on_leave,terminated'],
            'email'                 => ['nullable', 'email', 'max:150'],
            'birth_date'            => ['nullable', 'date'],
            'termination_date'      => ['nullable', 'date'],
            'salary'                => ['nullable', 'numeric', 'min:0'],
            'position_id'           => ['nullable', Rule::exists('positions', 'id')->where('company_id', $companyId)],
            'department_id'         => ['nullable', Rule::exists('departments', 'id')->where('company_id', $companyId)],
        ];

        $validator = Validator::make($row, $rules);

        if ($validator->fails()) {
            $messages = implode('; ', $validator->errors()->all());
            throw new \RuntimeException($messages);
        }

        return $validator->validated();
    }

    public function mapRow(array $validated, int $companyId): array
    {
        $row = $validated;
        $row['company_id'] = $companyId;

        if (isset($row['salary'])) {
            $row['salary'] = round((float) $row['salary'], 2);
        }

        foreach (['hire_date', 'birth_date', 'termination_date'] as $dateField) {
            if (isset($row[$dateField]) && $row[$dateField] !== null) {
                $row[$dateField] = Carbon::parse($row[$dateField])->toDateString();
            }
        }

        $now = now()->toDateTimeString();
        $row['created_at'] = $now;
        $row['updated_at'] = $now;

        return $row;
    }
}
