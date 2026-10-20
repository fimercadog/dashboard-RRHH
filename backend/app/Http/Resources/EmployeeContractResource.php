<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeContractResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'company_id'          => $this->company_id,
            'employee_id'         => $this->employee_id,
            'employee'            => $this->whenLoaded('employee', fn () => [
                'id'   => $this->employee->id,
                'name' => $this->employee->full_name,
            ]),
            'contract_number'     => $this->contract_number,
            'contract_type'       => $this->contract_type,
            'salary_modality'     => $this->salary_modality,
            'start_date'          => $this->start_date?->format('Y-m-d'),
            'end_date'            => $this->end_date?->format('Y-m-d'),
            'salary'              => $this->salary,
            'salary_type'         => $this->salary_type,
            'work_hours_per_week' => $this->work_hours_per_week,
            'risk_class'          => $this->risk_class,
            'status'              => $this->status,
            'termination_reason'  => $this->termination_reason,
            'terminated_at'       => $this->terminated_at?->format('Y-m-d'),
            'notes'               => $this->notes,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
