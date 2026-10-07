<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'job_vacancy_id', 'job_candidate_id',
        'applied_at', 'status', 'notes', 'hired_employee_id',
    ];

    protected $casts = ['applied_at' => 'date'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function vacancy(): BelongsTo { return $this->belongsTo(JobVacancy::class, 'job_vacancy_id'); }
    public function candidate(): BelongsTo { return $this->belongsTo(JobCandidate::class, 'job_candidate_id'); }
    public function hiredEmployee(): BelongsTo { return $this->belongsTo(Employee::class, 'hired_employee_id'); }
    public function interviews(): HasMany { return $this->hasMany(JobInterview::class); }
}
