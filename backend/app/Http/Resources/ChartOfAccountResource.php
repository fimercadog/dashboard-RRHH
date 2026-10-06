<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChartOfAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'code'             => $this->code,
            'name'             => $this->name,
            'type'             => $this->type,
            'nature'           => $this->nature,
            'parent_id'        => $this->parent_id,
            'level'            => $this->level,
            'allows_movements' => $this->allows_movements,
            'status'           => $this->status,
            'notes'            => $this->notes,
            'created_at'       => $this->created_at,
        ];
    }
}
