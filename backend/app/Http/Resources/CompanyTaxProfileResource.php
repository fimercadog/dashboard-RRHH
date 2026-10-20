<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyTaxProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->id,
            'company_id'           => $this->company_id,
            'legal_nature'         => $this->legal_nature,
            'tax_contributor_type' => $this->tax_contributor_type,
            'parafiscal_exempt'    => $this->parafiscal_exempt,
            'exempt_basis'         => $this->exempt_basis,
            'verified_by'          => $this->verified_by,
            'verified_at'          => $this->verified_at?->format('Y-m-d'),
            'review_status'        => $this->review_status,
            'valid_from'           => $this->valid_from?->format('Y-m-d'),
            'valid_until'          => $this->valid_until?->format('Y-m-d'),
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
