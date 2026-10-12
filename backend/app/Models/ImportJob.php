<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportJob extends Model
{
    protected $fillable = [
        'company_id', 'user_id', 'entity', 'status',
        'file_path', 'file_name',
        'total_rows', 'processed_rows', 'created_rows', 'skipped_rows', 'failed_rows',
        'errors', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'errors'       => 'array',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function user(): BelongsTo   { return $this->belongsTo(User::class); }
}
