<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContingencyEvent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ContingencySession::class, 'contingency_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
