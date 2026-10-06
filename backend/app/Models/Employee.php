<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'employee_code', 'first_name', 'middle_name', 'last_name', 'second_last_name',
        'identification_type', 'identification_number', 'email', 'phone', 'birth_date', 'gender',
        'address', 'city', 'emergency_contact_name', 'emergency_contact_phone', 'hire_date',
        'termination_date', 'employment_status', 'position_id', 'department_id', 'manager_id',
        'contract_type', 'salary', 'work_schedule', 'notes', 'avatar',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'salary' => 'decimal:2',
    ];

    public function getFullNameAttribute(): string
    {
        return collect([$this->first_name, $this->middle_name, $this->last_name, $this->second_last_name])
            ->filter()
            ->implode(' ');
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function position(): BelongsTo { return $this->belongsTo(Position::class); }
    public function manager(): BelongsTo { return $this->belongsTo(Employee::class, 'manager_id'); }
    public function attendances(): HasMany { return $this->hasMany(Attendance::class); }
    public function documents(): HasMany { return $this->hasMany(EmployeeDocument::class); }
    public function vacationRequests(): HasMany { return $this->hasMany(VacationRequest::class); }
    public function permissionRequests(): HasMany { return $this->hasMany(PermissionRequest::class); }
    public function sickLeaves(): HasMany { return $this->hasMany(SickLeave::class); }
}
