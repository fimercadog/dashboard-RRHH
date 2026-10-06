<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SickLeave extends Model
{
    /** @use HasFactory<\Database\Factories\SickLeaveFactory> */
    use HasFactory;

    protected $fillable = ['company_id', 'employee_id', 'start_date', 'end_date', 'days', 'type', 'description', 'document_path', 'status', 'notes'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
