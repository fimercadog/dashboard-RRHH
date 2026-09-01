<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContingencySession extends Model
{
    protected $guarded = [];

    protected $casts = [
        'enabled_modules' => 'array',
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ContingencyEvent::class);
    }
}
