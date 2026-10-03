<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'sku', 'name', 'description',
        'category_id', 'brand_id', 'unit_id',
        'type', 'cost_price', 'sale_price', 'min_stock',
        'inventory_account_code', 'cogs_account_code', 'sale_account_code',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'float',
            'sale_price' => 'float',
            'min_stock'  => 'float',
        ];
    }

    public function hasStock(): bool
    {
        return $this->type !== 'service';
    }

    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function brand(): BelongsTo    { return $this->belongsTo(Brand::class); }
    public function unit(): BelongsTo     { return $this->belongsTo(Unit::class); }
    public function company(): BelongsTo  { return $this->belongsTo(Company::class); }
    public function stocks(): HasMany     { return $this->hasMany(ProductStock::class); }
    public function movements(): HasMany  { return $this->hasMany(StockMovement::class); }
}
