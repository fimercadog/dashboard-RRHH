<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JobApplicationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'status'            => $this->status,
            'applied_at'        => $this->applied_at?->toDateString(),
            'notes'             => $this->notes,
            'hired_employee_id' => $this->hired_employee_id,
            'vacancy'           => $this->whenLoaded('vacancy', fn() => ['id' => $this->vacancy->id, 'title' => $this->vacancy->title, 'status' => $this->vacancy->status]),
            'candidate'         => $this->whenLoaded('candidate', fn() => ['id' => $this->candidate->id, 'first_name' => $this->candidate->first_name, 'last_name' => $this->candidate->last_name, 'email' => $this->candidate->email, 'phone' => $this->candidate->phone]),
            'created_at'        => $this->created_at,
        ];
    }
}
