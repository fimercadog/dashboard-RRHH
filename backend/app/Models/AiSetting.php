<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiSetting extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'provider',
        'model',
        'api_key',
        'base_url',
        'system_prompt',
        'context_messages',
        'is_active',
        'last_tested_at',
    ];

    protected $casts = [
        'api_key'          => 'encrypted',
        'is_active'        => 'boolean',
        'last_tested_at'   => 'datetime',
        'context_messages' => 'integer',
    ];

    protected $hidden = ['api_key'];

    public const PROVIDERS = ['openai', 'anthropic', 'openai_compat'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** True si tiene API key y está marcado activo. */
    public function isConnected(): bool
    {
        return $this->is_active && filled($this->api_key);
    }
}
