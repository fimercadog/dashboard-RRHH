<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    public const STATUSES = ['draft', 'sent', 'partial', 'received', 'cancelled'];

    protected $fillable = [
        'company_id', 'supplier_id', 'warehouse_id', 'user_id',
        'number', 'date', 'expected_date', 'status',
        'subtotal', 'tax', 'total', 'notes',
    ];

    protected $casts = [
        'date' => 'date', 'expected_date' => 'date',
        'subtotal' => 'float', 'tax' => 'float', 'total' => 'float',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(PurchaseOrderItem::class); }
    public function receipts(): HasMany { return $this->hasMany(PurchaseReceipt::class); }
    public function invoices(): HasMany { return $this->hasMany(PurchaseInvoice::class); }
}
