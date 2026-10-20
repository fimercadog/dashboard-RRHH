<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollItem extends Model
{
    protected $fillable = [
        'company_id', 'payroll_run_id', 'employee_id', 'contract_id',
        'salary_snapshot', 'salary_modality_snapshot', 'ibc',
        'worked_days', 'vacation_days', 'sick_days_common', 'sick_days_labor', 'unpaid_days',
        'salary_earned', 'transport_allowance', 'vacation_pay', 'overtime_pay',
        'bonuses', 'other_earnings', 'total_earned',
        'health_employee', 'pension_employee', 'solidarity_fund', 'withholding_tax',
        'loan_deductions', 'other_deductions', 'total_deductions', 'net_pay',
        'health_employer', 'pension_employer', 'arl_employer', 'ccf_employer',
        'sena_employer', 'icbf_employer', 'parafiscal_exempt', 'parafiscal_exempt_basis',
        'total_employer_cost',
        'severance_provision', 'interest_provision', 'prima_provision', 'vacation_provision',
        'status', 'notes',
    ];

    protected $casts = [
        'parafiscal_exempt' => 'boolean',
    ];

    public function company(): BelongsTo   { return $this->belongsTo(Company::class); }
    public function run(): BelongsTo       { return $this->belongsTo(PayrollRun::class, 'payroll_run_id'); }
    public function employee(): BelongsTo  { return $this->belongsTo(Employee::class); }
    public function contract(): BelongsTo  { return $this->belongsTo(EmployeeContract::class, 'contract_id'); }
    public function lines(): HasMany       { return $this->hasMany(PayrollItemLine::class); }
}
