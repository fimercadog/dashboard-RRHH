<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReceipt extends Model
{
    public const TYPES    = ['receipt', 'return'];
    public const STATUSES = ['draft', 'posted', 'cancelled'];

    protected $fillable = [
        'company_id', 'purchase_order_id', 'supplier_id', 'warehouse_id', 'user_id',
        'number', 'date', 'type', 'status', 'notes',
    ];

    protected $casts = ['date' => 'date'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(PurchaseReceiptItem::class); }
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'reference_id')
                    ->where('reference_type', 'purchase_receipt');
    }
}
