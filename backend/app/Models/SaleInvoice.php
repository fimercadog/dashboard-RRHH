<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SaleInvoice extends Model
{
    public const STATUSES = ['draft', 'posted', 'partially_paid', 'paid', 'cancelled'];
    public const TYPES    = ['invoice', 'return'];

    protected $fillable = [
        'company_id', 'client_id', 'sale_order_id', 'user_id',
        'number', 'date', 'due_date', 'type', 'status',
        'subtotal', 'discount_total', 'tax', 'total', 'notes',
    ];

    protected $casts = [
        'date'           => 'date',
        'due_date'       => 'date',
        'subtotal'       => 'float',
        'discount_total' => 'float',
        'tax'            => 'float',
        'total'          => 'float',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function saleOrder(): BelongsTo { return $this->belongsTo(SaleOrder::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(SaleInvoiceItem::class); }
    public function accountReceivable(): HasOne { return $this->hasOne(AccountsReceivable::class); }
}
