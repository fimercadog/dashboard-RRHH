<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseInvoice extends Model
{
    public const STATUSES = ['draft', 'posted', 'partially_paid', 'paid', 'cancelled'];

    protected $fillable = [
        'company_id', 'supplier_id', 'purchase_order_id', 'purchase_receipt_id', 'user_id',
        'number', 'date', 'due_date',
        'subtotal', 'tax', 'total',
        'status', 'notes',
    ];

    protected $casts = [
        'date'     => 'date',
        'due_date' => 'date',
        'subtotal' => 'float',
        'tax'      => 'float',
        'total'    => 'float',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function purchaseReceipt(): BelongsTo { return $this->belongsTo(PurchaseReceipt::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function accountPayable(): HasOne { return $this->hasOne(AccountPayable::class); }
}
