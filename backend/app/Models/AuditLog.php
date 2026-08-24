<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    /** @use HasFactory<\Database\Factories\AuditLogFactory> */
    use HasFactory;

    protected $fillable = ['company_id', 'user_id', 'action', 'module', 'entity', 'entity_id', 'old_values', 'new_values', 'ip_address'];
    protected $casts = ['old_values' => 'array', 'new_values' => 'array'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
