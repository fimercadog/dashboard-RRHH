<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReview extends Model
{
    use HasFactory;

    protected $attributes = ['status' => 'draft'];

    protected $fillable = [
        'company_id', 'employee_id', 'reviewer_id',
        'period_start', 'period_end',
        'overall_rating', 'goals_rating', 'competencies_rating',
        'strengths', 'improvements', 'goals_next_period',
        'status', 'submitted_at', 'acknowledged_at',
    ];

    protected $casts = [
        'period_start'     => 'date',
        'period_end'       => 'date',
        'submitted_at'     => 'datetime',
        'acknowledged_at'  => 'datetime',
        'overall_rating'   => 'integer',
        'goals_rating'     => 'integer',
        'competencies_rating' => 'integer',
    ];

    public function company(): BelongsTo  { return $this->belongsTo(Company::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewer_id'); }
}
