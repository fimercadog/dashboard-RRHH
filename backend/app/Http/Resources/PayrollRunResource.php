<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayrollRunResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->id,
            'company_id'           => $this->company_id,
            'payroll_period_id'    => $this->payroll_period_id,
            'run_number'           => $this->run_number,
            'status'               => $this->status,
            'total_employees'      => $this->total_employees,
            'total_devengado'      => $this->total_devengado,
            'total_deducciones'    => $this->total_deducciones,
            'total_neto'           => $this->total_neto,
            'total_aportes_empresa' => $this->total_aportes_empresa,
            'tax_profile_id'       => $this->tax_profile_id,
            'error_log'            => $this->error_log,
            'executed_by'          => $this->executed_by,
            'executed_at'          => $this->executed_at,
            'created_at'           => $this->created_at,
            'items'                => $this->whenLoaded('items'),
        ];
    }
}
