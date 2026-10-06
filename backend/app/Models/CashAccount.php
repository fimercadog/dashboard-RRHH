<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashAccount extends Model
{
    public const TYPES    = ['cash', 'bank', 'savings'];
    public const STATUSES = ['active', 'inactive'];

    protected $fillable = [
        'company_id', 'name', 'type', 'currency', 'balance', 'status', 'notes',
    ];

    protected $casts = [
        'balance' => 'float',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function transactions(): HasMany { return $this->hasMany(FinancialTransaction::class); }
    public function outgoingTransfers(): HasMany { return $this->hasMany(Transfer::class, 'from_account_id'); }
    public function incomingTransfers(): HasMany { return $this->hasMany(Transfer::class, 'to_account_id'); }
}
