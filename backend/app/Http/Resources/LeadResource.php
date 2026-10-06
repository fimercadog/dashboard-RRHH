<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // ip_address se captura para el registro Ley 1581; no se expone al panel.
        return [
            'id' => $this->id,
            'name' => $this->name,
            'company_name' => $this->company_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'employee_count' => $this->employee_count,
            'priority_module' => $this->priority_module,
            'message' => $this->message,
            'source' => $this->source,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
