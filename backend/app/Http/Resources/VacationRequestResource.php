<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VacationRequestResource extends JsonResource
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
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'requested_days' => $this->requested_days,
            'reason' => $this->reason,
            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'rejection_reason' => $this->rejection_reason,
        ];
    }
}
