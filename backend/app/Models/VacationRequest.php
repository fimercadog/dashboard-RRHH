<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacationRequest extends Model
{
    /** @use HasFactory<\Database\Factories\VacationRequestFactory> */
    use HasFactory;

    protected $fillable = ['company_id', 'employee_id', 'start_date', 'end_date', 'requested_days', 'reason', 'status', 'approved_by', 'approved_at', 'rejection_reason'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'approved_at' => 'datetime'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
