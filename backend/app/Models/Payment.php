<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const METHODS  = ['cash', 'transfer', 'check', 'card', 'other'];
    public const STATUSES = ['active', 'cancelled'];

    protected $fillable = [
        'company_id', 'cash_account_id', 'user_id',
        'payable_type', 'payable_id',
        'amount', 'date', 'method', 'reference', 'notes',
        'status', 'journal_entry_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'date'   => 'date',
    ];

    public function company(): BelongsTo     { return $this->belongsTo(Company::class); }
    public function cashAccount(): BelongsTo { return $this->belongsTo(CashAccount::class); }
    public function user(): BelongsTo        { return $this->belongsTo(User::class); }
}
