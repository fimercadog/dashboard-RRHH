<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappSetting extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'phone_number_id',
        'waba_id',
        'app_secret',
        'access_token',
        'webhook_verify_token',
        'is_active',
        'webhook_verified_at',
    ];

    protected $casts = [
        'access_token'       => 'encrypted',  // secreto real
        'app_secret'         => 'encrypted',
        'is_active'          => 'boolean',
        'webhook_verified_at' => 'datetime',
        // webhook_verify_token: plain — es un slug público en la URL, no un secreto.
    ];

    // Ocultar secretos al serializar.
    protected $hidden = ['access_token', 'app_secret'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** True si webhook verificado y access_token presente. */
    public function isConnected(): bool
    {
        return $this->is_active
            && $this->webhook_verified_at !== null
            && filled($this->access_token);
    }
}
