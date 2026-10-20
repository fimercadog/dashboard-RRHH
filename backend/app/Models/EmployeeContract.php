<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeContract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'employee_id', 'contract_number', 'contract_type',
        'salary_modality', 'start_date', 'end_date', 'salary', 'salary_type',
        'work_hours_per_week', 'risk_class', 'status', 'termination_reason',
        'terminated_at', 'notes', 'created_by',
    ];

    protected $casts = [
        'start_date'    => 'date',
        'end_date'      => 'date',
        'terminated_at' => 'date',
        'salary'        => 'decimal:2',
    ];

    public function company(): BelongsTo  { return $this->belongsTo(Company::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function creator(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function payrollItems(): HasMany { return $this->hasMany(PayrollItem::class, 'contract_id'); }

    /** Scope: contratos activos. */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Salario vigente para una fecha dada.
     * Si el contrato está activo en esa fecha, devuelve el salario; si no, null.
     */
    public function salaryForDate(\DateTimeInterface $date): ?float
    {
        $d = \Carbon\Carbon::instance($date);
        if ($this->start_date->gt($d)) {
            return null;
        }
        if ($this->end_date && $this->end_date->lt($d)) {
            return null;
        }
        return (float) $this->salary;
    }
}
