<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    public const STATUSES = ['draft', 'sent', 'accepted', 'expired', 'cancelled'];

    protected $fillable = [
        'company_id', 'client_id', 'deal_id', 'user_id',
        'number', 'date', 'valid_until', 'status',
        'subtotal', 'discount_total', 'tax', 'total', 'notes',
        'client_uuid',
    ];

    protected $casts = [
        'date'           => 'date',
        'valid_until'    => 'date',
        'subtotal'       => 'float',
        'discount_total' => 'float',
        'tax'            => 'float',
        'total'          => 'float',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function deal(): BelongsTo { return $this->belongsTo(Deal::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(QuoteItem::class); }
}
