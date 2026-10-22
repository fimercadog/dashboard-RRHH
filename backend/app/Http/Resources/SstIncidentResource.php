<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SstIncidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'company_id'    => $this->company_id,
            'employee_id'   => $this->employee_id,
            'employee'      => $this->whenLoaded('employee', fn () => [
                'id'         => $this->employee->id,
                'first_name' => $this->employee->first_name,
                'last_name'  => $this->employee->last_name,
            ]),
            'incident_date' => $this->incident_date?->toDateString(),
            'type'          => $this->type,
            'severity'      => $this->severity,
            'location'      => $this->location,
            'description'   => $this->description,
            'action_taken'  => $this->action_taken,
            'days_lost'     => $this->days_lost,
            'status'        => $this->status,
            'reported_by'   => $this->reported_by,
            'resolved_at'   => $this->resolved_at?->toISOString(),
            'created_at'    => $this->created_at?->toISOString(),
            'updated_at'    => $this->updated_at?->toISOString(),
        ];
    }
}
