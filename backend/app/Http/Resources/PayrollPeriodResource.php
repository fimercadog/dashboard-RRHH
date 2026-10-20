<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayrollPeriodResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                     => $this->id,
            'company_id'             => $this->company_id,
            'name'                   => $this->name,
            'period_type'            => $this->period_type,
            'start_date'             => $this->start_date?->format('Y-m-d'),
            'end_date'               => $this->end_date?->format('Y-m-d'),
            'payment_date'           => $this->payment_date?->format('Y-m-d'),
            'status'                 => $this->status,
            'is_complementary'       => $this->is_complementary,
            'original_period_id'     => $this->original_period_id,
            'authorized_reversal'    => $this->authorized_reversal,
            'reversal_reason'        => $this->reversal_reason,
            'closed_by'              => $this->closed_by,
            'closed_at'              => $this->closed_at,
            'notes'                  => $this->notes,
            'created_at'             => $this->created_at,
            'updated_at'             => $this->updated_at,
        ];
    }
}
