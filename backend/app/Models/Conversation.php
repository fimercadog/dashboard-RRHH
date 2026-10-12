<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'whatsapp_setting_id',
        'contact_name',
        'contact_phone',
        'channel',
        'status',
        'assigned_to',
        'last_message_at',
        'last_customer_msg_at',
        'meta_conversation_id',
    ];

    protected $casts = [
        'status'               => 'string',
        'last_message_at'      => 'datetime',
        'last_customer_msg_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function whatsappSetting(): BelongsTo
    {
        return $this->belongsTo(WhatsappSetting::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class);
    }

    public function latestMessage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ConversationMessage::class)->latestOfMany();
    }

    public function isWithin24hWindow(): bool
    {
        // Carbon 3 (Laravel 11) diffInHours() is signed — use isAfter() for correctness.
        return $this->last_customer_msg_at !== null
            && $this->last_customer_msg_at->isAfter(now()->subHours(24));
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeTransferred($query)
    {
        return $query->where('status', 'transferred');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }
}
