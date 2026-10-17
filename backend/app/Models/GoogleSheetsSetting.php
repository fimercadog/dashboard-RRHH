<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleSheetsSetting extends Model
{
    protected $fillable = [
        'company_id',
        'api_key',
        'is_active',
        'connected_at',
    ];

    protected $casts = [
        'api_key'      => 'encrypted',
        'is_active'    => 'boolean',
        'connected_at' => 'datetime',
    ];

    protected $hidden = ['api_key'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isConnected(): bool
    {
        return $this->is_active && filled($this->api_key);
    }
}
