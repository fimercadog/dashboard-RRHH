<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JobVacancyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'description'     => $this->description,
            'status'          => $this->status,
            'vacancies_count' => $this->vacancies_count,
            'opened_at'       => $this->opened_at?->toDateString(),
            'closed_at'       => $this->closed_at?->toDateString(),
            'department'      => $this->whenLoaded('department', fn() => ['id' => $this->department->id, 'name' => $this->department->name]),
            'created_at'      => $this->created_at,
        ];
    }
}
