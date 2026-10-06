<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingAccountConfig extends Model
{
    protected $fillable = ['company_id', 'config_key', 'account_id'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
