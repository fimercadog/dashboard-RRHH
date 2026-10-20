<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeWorkHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'employee_id'    => $this->employee_id,
            'event_type'     => $this->event_type,
            'title'          => $this->title,
            'description'    => $this->description,
            'effective_date' => $this->effective_date,
            'created_at'     => $this->created_at,
        ];
    }
}
