<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SickLeaveResource extends JsonResource
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
            'days' => $this->days,
            'type' => $this->type,
            'description' => $this->description,
            'document_path' => $this->document_path,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
