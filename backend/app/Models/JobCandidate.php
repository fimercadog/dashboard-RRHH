<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobCandidate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'first_name', 'last_name', 'identification_number',
        'email', 'phone', 'resume_url', 'observations',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function applications(): HasMany { return $this->hasMany(JobApplication::class); }
}
