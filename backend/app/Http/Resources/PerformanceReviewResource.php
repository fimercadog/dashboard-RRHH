<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerformanceReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'company_id'           => $this->company_id,
            'employee_id'          => $this->employee_id,
            'employee'             => $this->whenLoaded('employee', fn () => [
                'id'         => $this->employee->id,
                'first_name' => $this->employee->first_name,
                'last_name'  => $this->employee->last_name,
            ]),
            'reviewer_id'          => $this->reviewer_id,
            'period_start'         => $this->period_start?->toDateString(),
            'period_end'           => $this->period_end?->toDateString(),
            'overall_rating'       => $this->overall_rating,
            'goals_rating'         => $this->goals_rating,
            'competencies_rating'  => $this->competencies_rating,
            'strengths'            => $this->strengths,
            'improvements'         => $this->improvements,
            'goals_next_period'    => $this->goals_next_period,
            'status'               => $this->status,
            'submitted_at'         => $this->submitted_at?->toISOString(),
            'acknowledged_at'      => $this->acknowledged_at?->toISOString(),
            'created_at'           => $this->created_at?->toISOString(),
            'updated_at'           => $this->updated_at?->toISOString(),
        ];
    }
}
