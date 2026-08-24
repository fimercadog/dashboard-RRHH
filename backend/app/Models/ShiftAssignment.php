<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftAssignment extends Model
{
    /** @use HasFactory<\Database\Factories\ShiftAssignmentFactory> */
    use HasFactory;

    protected $fillable = ['company_id', 'employee_id', 'shift_id', 'date'];
    protected $casts = ['date' => 'date'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function shift(): BelongsTo { return $this->belongsTo(Shift::class); }
}
