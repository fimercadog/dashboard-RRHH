<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'     => $this->id,
            'name'   => $this->name,
            'status' => $this->status,
            'parent' => $this->whenLoaded('parent', fn () => ['id' => $this->parent->id, 'name' => $this->parent->name]),
        ];
    }
}
