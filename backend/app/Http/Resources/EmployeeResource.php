<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'employee_code' => $this->employee_code,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'second_last_name' => $this->second_last_name,
            'full_name' => $this->full_name,
            'identification_type' => $this->identification_type,
            'identification_number' => $this->identification_number,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth_date' => $this->birth_date,
            'hire_date' => $this->hire_date,
            'termination_date' => $this->termination_date,
            'employment_status' => $this->employment_status,
            'department' => $this->whenLoaded('department'),
            'position' => $this->whenLoaded('position'),
            'manager' => $this->whenLoaded('manager'),
            'contract_type' => $this->contract_type,
            'salary' => $this->salary,
            'work_schedule' => $this->work_schedule,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
