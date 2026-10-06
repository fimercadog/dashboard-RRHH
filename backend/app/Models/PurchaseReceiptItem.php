<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReceiptItem extends Model
{
    protected $fillable = [
        'purchase_receipt_id', 'product_id', 'quantity', 'unit_cost',
    ];

    protected $casts = [
        'quantity'  => 'float',
        'unit_cost' => 'float',
    ];

    public function purchaseReceipt(): BelongsTo { return $this->belongsTo(PurchaseReceipt::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
