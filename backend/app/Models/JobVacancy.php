<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobVacancy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'department_id', 'title', 'description',
        'opened_at', 'closed_at', 'status', 'vacancies_count',
    ];

    protected $casts = [
        'opened_at' => 'date',
        'closed_at' => 'date',
        'vacancies_count' => 'integer',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function applications(): HasMany { return $this->hasMany(JobApplication::class); }
}
