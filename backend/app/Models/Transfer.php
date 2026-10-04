<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transfer extends Model
{
    public const STATUSES = ['active', 'cancelled'];

    protected $fillable = [
        'company_id', 'from_account_id', 'to_account_id', 'user_id',
        'amount', 'date', 'reference', 'notes', 'status',
    ];

    protected $casts = [
        'amount' => 'float',
        'date'   => 'date',
    ];

    public function company(): BelongsTo     { return $this->belongsTo(Company::class); }
    public function fromAccount(): BelongsTo { return $this->belongsTo(CashAccount::class, 'from_account_id'); }
    public function toAccount(): BelongsTo   { return $this->belongsTo(CashAccount::class, 'to_account_id'); }
    public function user(): BelongsTo        { return $this->belongsTo(User::class); }
}
