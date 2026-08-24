<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    /** @use HasFactory<\Database\Factories\ShiftFactory> */
    use HasFactory;

    protected $fillable = ['company_id', 'name', 'start_time', 'end_time', 'break_minutes', 'status'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function assignments(): HasMany { return $this->hasMany(ShiftAssignment::class); }
}
