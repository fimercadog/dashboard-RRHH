<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeDocumentFactory> */
    use HasFactory;

    protected $fillable = ['company_id', 'employee_id', 'document_type', 'name', 'file_path', 'issue_date', 'expiration_date', 'status', 'notes'];
    protected $casts = ['issue_date' => 'date', 'expiration_date' => 'date'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
