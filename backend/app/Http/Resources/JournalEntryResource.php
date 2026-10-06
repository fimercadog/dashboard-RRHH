<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'number'               => $this->number,
            'date'                 => $this->date,
            'description'          => $this->description,
            'status'               => $this->status,
            'reference_type'       => $this->reference_type,
            'reference_id'         => $this->reference_id,
            'accounting_period_id' => $this->accounting_period_id,
            'reversed_by_entry_id' => $this->reversed_by_entry_id,
            'reversal_of_entry_id' => $this->reversal_of_entry_id,
            'user_id'              => $this->user_id,
            'lines'                => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line) => [
                'id'          => $line->id,
                'account_id'  => $line->account_id,
                'account_code'=> $line->account?->code,
                'account_name'=> $line->account?->name,
                'description' => $line->description,
                'debit'       => (float) $line->debit,
                'credit'      => (float) $line->credit,
                'sequence'    => $line->sequence,
            ])),
            'created_at'           => $this->created_at,
        ];
    }
}
