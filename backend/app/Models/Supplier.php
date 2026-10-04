<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'nit', 'email', 'phone', 'address', 'payment_terms', 'status',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function purchaseOrders(): HasMany { return $this->hasMany(PurchaseOrder::class); }
    public function purchaseInvoices(): HasMany { return $this->hasMany(PurchaseInvoice::class); }
    public function accountsPayable(): HasMany { return $this->hasMany(AccountPayable::class); }
}
