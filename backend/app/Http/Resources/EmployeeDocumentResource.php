<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeDocumentResource extends JsonResource
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
            'document_type' => $this->document_type,
            'name' => $this->name,
            'file_path' => $this->file_path,
            'issue_date' => $this->issue_date,
            'expiration_date' => $this->expiration_date,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
