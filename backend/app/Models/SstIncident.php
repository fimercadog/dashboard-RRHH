<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SstIncident extends Model
{
    use HasFactory;

    protected $attributes = ['status' => 'reported'];

    protected $fillable = [
        'company_id', 'employee_id', 'incident_date', 'type', 'severity',
        'location', 'description', 'action_taken', 'days_lost', 'status',
        'reported_by', 'resolved_at',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'resolved_at'   => 'datetime',
        'days_lost'     => 'integer',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }
}
