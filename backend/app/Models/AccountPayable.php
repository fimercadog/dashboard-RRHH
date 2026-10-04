<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountPayable extends Model
{
    public const STATUSES = ['open', 'partially_paid', 'paid', 'cancelled'];

    protected $table = 'accounts_payable';

    protected $fillable = [
        'company_id', 'supplier_id', 'purchase_invoice_id',
        'amount', 'balance', 'due_date', 'status',
    ];

    protected $casts = [
        'amount'   => 'float',
        'balance'  => 'float',
        'due_date' => 'date',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function purchaseInvoice(): BelongsTo { return $this->belongsTo(PurchaseInvoice::class); }
}
