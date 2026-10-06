<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTransaction extends Model
{
    public const TYPES = ['income', 'expense', 'transfer_in', 'transfer_out'];

    protected $fillable = [
        'company_id', 'cash_account_id', 'user_id',
        'type', 'amount', 'date',
        'reference_type', 'reference_id', 'description',
    ];

    protected $casts = [
        'amount' => 'float',
        'date'   => 'date',
    ];

    public function company(): BelongsTo     { return $this->belongsTo(Company::class); }
    public function cashAccount(): BelongsTo { return $this->belongsTo(CashAccount::class); }
    public function user(): BelongsTo        { return $this->belongsTo(User::class); }
}
