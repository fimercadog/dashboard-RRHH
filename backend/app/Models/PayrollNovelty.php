<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollNovelty extends Model
{
    protected $fillable = [
        'company_id', 'payroll_period_id', 'employee_id', 'novelty_type',
        'description', 'amount', 'is_recurrent', 'applies_ibc', 'status',
        'applied_run_id', 'approved_by', 'approved_at', 'notes', 'created_by',
    ];

    protected $casts = [
        'is_recurrent'  => 'boolean',
        'applies_ibc'   => 'boolean',
        'approved_at'   => 'datetime',
        'amount'        => 'decimal:2',
    ];

    public function company(): BelongsTo   { return $this->belongsTo(Company::class); }
    public function period(): BelongsTo    { return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id'); }
    public function employee(): BelongsTo  { return $this->belongsTo(Employee::class); }
    public function appliedRun(): BelongsTo { return $this->belongsTo(PayrollRun::class, 'applied_run_id'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
