<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleInvoiceItem extends Model
{
    protected $fillable = [
        'sale_invoice_id', 'product_id', 'quantity', 'unit_price',
        'discount_pct', 'subtotal', 'cost_at_time',
    ];

    protected $casts = [
        'quantity'     => 'float',
        'unit_price'   => 'float',
        'discount_pct' => 'float',
        'subtotal'     => 'float',
        'cost_at_time' => 'float',
    ];

    public function saleInvoice(): BelongsTo { return $this->belongsTo(SaleInvoice::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
