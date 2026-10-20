<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeWorkHistory extends Model
{
    protected $table = 'employee_work_history';

    protected $fillable = [
        'company_id', 'employee_id', 'event_type', 'title', 'description', 'effective_date',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
