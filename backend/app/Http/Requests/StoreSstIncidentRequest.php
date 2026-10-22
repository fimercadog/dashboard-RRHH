<?php

namespace App\Http\Requests;

class StoreSstIncidentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'employee_id'   => ['nullable', $this->ownedExists('employees')],
            'incident_date' => ['required', 'date', 'before_or_equal:today'],
            'type'          => ['required', 'in:near_miss,incident,accident,occupational_disease'],
            'severity'      => ['required', 'in:low,medium,high,critical'],
            'location'      => ['nullable', 'string', 'max:255'],
            'description'   => ['required', 'string', 'max:2000'],
            'action_taken'  => ['nullable', 'string', 'max:2000'],
            'days_lost'     => ['nullable', 'integer', 'min:0'],
            'status'        => ['nullable', 'in:reported,investigating,resolved,closed'],
            'reported_by'   => ['nullable', 'integer', $this->ownedExists('users')],
        ];
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'incident_date' => 'fecha del incidente',
            'action_taken'  => 'acción tomada',
            'days_lost'     => 'días perdidos',
            'reported_by'   => 'reportado por',
            'location'      => 'ubicación',
        ]);
    }
}
