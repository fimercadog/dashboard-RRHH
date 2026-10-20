<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $fillable = [
        'company_id', 'payroll_period_id', 'run_number',
        'total_employees', 'total_devengado', 'total_deducciones',
        'total_neto', 'total_aportes_empresa',
        'tax_profile_id', 'error_log', 'executed_by', 'executed_at',
        // status y parameters_snapshot solo via forceFill (PayrollService)
    ];

    protected $casts = [
        'parameters_snapshot' => 'array',
        'executed_at'         => 'datetime',
    ];

    public function company(): BelongsTo       { return $this->belongsTo(Company::class); }
    public function period(): BelongsTo        { return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id'); }
    public function taxProfile(): BelongsTo    { return $this->belongsTo(CompanyTaxProfile::class, 'tax_profile_id'); }
    public function executor(): BelongsTo      { return $this->belongsTo(User::class, 'executed_by'); }
    public function items(): HasMany           { return $this->hasMany(PayrollItem::class); }
}
