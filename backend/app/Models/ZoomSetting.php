<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoomSetting extends Model
{
    protected $fillable = [
        'company_id',
        'account_id',
        'client_id',
        'client_secret',
        'is_active',
        'connected_at',
    ];

    protected $casts = [
        'client_secret' => 'encrypted',
        'is_active'     => 'boolean',
        'connected_at'  => 'datetime',
    ];

    protected $hidden = ['client_secret'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isConnected(): bool
    {
        return $this->is_active
            && filled($this->account_id)
            && filled($this->client_id)
            && filled($this->client_secret);
    }
}
