<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStock extends Model
{
    protected $table = 'product_stock';

    protected $fillable = ['company_id', 'product_id', 'warehouse_id', 'quantity', 'avg_cost'];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'avg_cost' => 'float',
        ];
    }

    public function product(): BelongsTo   { return $this->belongsTo(Product::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function company(): BelongsTo   { return $this->belongsTo(Company::class); }
}
