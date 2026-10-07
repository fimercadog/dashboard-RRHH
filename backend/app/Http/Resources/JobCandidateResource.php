<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JobCandidateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                    => $this->id,
            'first_name'            => $this->first_name,
            'last_name'             => $this->last_name,
            'identification_number' => $this->identification_number,
            'email'                 => $this->email,
            'phone'                 => $this->phone,
            'resume_url'            => $this->resume_url,
            'observations'          => $this->observations,
            'created_at'            => $this->created_at,
        ];
    }
}
