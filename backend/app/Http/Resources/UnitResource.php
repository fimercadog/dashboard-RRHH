<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'abbreviation' => $this->abbreviation, 'status' => $this->status];
    }
}
