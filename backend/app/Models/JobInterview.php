<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobInterview extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'job_application_id', 'interviewer_id',
        'scheduled_at', 'type', 'result', 'notes',
    ];

    protected $casts = ['scheduled_at' => 'datetime'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function application(): BelongsTo { return $this->belongsTo(JobApplication::class, 'job_application_id'); }
    public function interviewer(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'interviewer_id'); }
}
