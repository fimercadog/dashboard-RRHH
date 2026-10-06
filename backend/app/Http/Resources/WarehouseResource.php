<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'location' => $this->location, 'status' => $this->status];
    }
}
