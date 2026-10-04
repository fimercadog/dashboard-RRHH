<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    protected $fillable = [
        'quote_id', 'product_id', 'quantity', 'unit_price', 'discount_pct', 'subtotal',
    ];

    protected $casts = [
        'quantity'     => 'float',
        'unit_price'   => 'float',
        'discount_pct' => 'float',
        'subtotal'     => 'float',
    ];

    public function quote(): BelongsTo { return $this->belongsTo(Quote::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
