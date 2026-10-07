<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JobInterviewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'type'         => $this->type,
            'result'       => $this->result,
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'notes'        => $this->notes,
            'application'  => $this->whenLoaded('application', fn() => [
                'id'        => $this->application->id,
                'candidate' => $this->application->relationLoaded('candidate') ? ['id' => $this->application->candidate->id, 'first_name' => $this->application->candidate->first_name, 'last_name' => $this->application->candidate->last_name] : null,
                'vacancy'   => $this->application->relationLoaded('vacancy') ? ['id' => $this->application->vacancy->id, 'title' => $this->application->vacancy->title] : null,
            ]),
            'interviewer'  => $this->whenLoaded('interviewer', fn() => ['id' => $this->interviewer->id, 'name' => $this->interviewer->name]),
            'created_at'   => $this->created_at,
        ];
    }
}
