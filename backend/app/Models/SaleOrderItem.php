<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleOrderItem extends Model
{
    protected $fillable = [
        'sale_order_id', 'product_id', 'quantity', 'unit_price',
        'discount_pct', 'subtotal', 'delivered_qty',
    ];

    protected $casts = [
        'quantity'     => 'float',
        'unit_price'   => 'float',
        'discount_pct' => 'float',
        'subtotal'     => 'float',
        'delivered_qty'=> 'float',
    ];

    public function saleOrder(): BelongsTo { return $this->belongsTo(SaleOrder::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
