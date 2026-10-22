<?php

namespace App\Http\Requests;

class StorePerformanceReviewRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'employee_id'          => ['required', $this->ownedExists('employees')],
            'reviewer_id'          => ['nullable', 'integer', $this->ownedExists('users')],
            'period_start'         => ['required', 'date'],
            'period_end'           => ['required', 'date', 'after_or_equal:period_start'],
            'overall_rating'       => ['nullable', 'integer', 'min:1', 'max:5'],
            'goals_rating'         => ['nullable', 'integer', 'min:1', 'max:5'],
            'competencies_rating'  => ['nullable', 'integer', 'min:1', 'max:5'],
            'strengths'            => ['nullable', 'string', 'max:2000'],
            'improvements'         => ['nullable', 'string', 'max:2000'],
            'goals_next_period'    => ['nullable', 'string', 'max:2000'],
            'status'               => ['nullable', 'in:draft,submitted,acknowledged'],
        ];
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'period_start'        => 'inicio del período',
            'period_end'          => 'fin del período',
            'overall_rating'      => 'calificación general',
            'goals_rating'        => 'calificación objetivos',
            'competencies_rating' => 'calificación competencias',
            'strengths'           => 'fortalezas',
            'improvements'        => 'oportunidades de mejora',
            'goals_next_period'   => 'metas próximo período',
            'reviewer_id'         => 'evaluador',
        ]);
    }
}
