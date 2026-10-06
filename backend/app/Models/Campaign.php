<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaign extends Model
{
    use HasFactory;
    public const TYPES    = ['individual', 'mass', 'announcement', 'reminder', 'campaign'];
    public const STATUSES = ['draft', 'scheduled', 'sent', 'cancelled'];
    public const SOURCES  = ['erp_employees', 'erp_clients', 'erp_leads', 'csv', 'google_sheets'];

    protected $fillable = [
        'company_id', 'created_by',
        'name', 'type', 'status',
        'audience_source', 'audience_filters',
        'message_subject', 'message_body',
        'scheduled_at', 'sent_at',
    ];

    protected $casts = [
        'audience_filters' => 'array',
        'scheduled_at'     => 'datetime',
        'sent_at'          => 'datetime',
    ];

    public function company(): BelongsTo   { return $this->belongsTo(Company::class); }
    public function creator(): BelongsTo   { return $this->belongsTo(User::class, 'created_by'); }
}
