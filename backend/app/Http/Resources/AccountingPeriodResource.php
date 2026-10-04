<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountingPeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'start_date' => $this->start_date,
            'end_date'   => $this->end_date,
            'status'     => $this->status,
            'closed_by'  => $this->closed_by,
            'closed_at'  => $this->closed_at,
            'entries_count' => $this->whenCounted('journalEntries'),
            'created_at' => $this->created_at,
        ];
    }
}
