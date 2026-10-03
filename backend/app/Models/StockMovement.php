<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'company_id', 'product_id', 'warehouse_id',
        'type', 'quantity', 'unit_cost', 'total_cost',
        'transfer_id', 'reference_type', 'reference_id',
        'notes', 'user_id',
        'journal_entry_id', 'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity'   => 'float',
            'unit_cost'  => 'float',
            'total_cost' => 'float',
            'posted_at'  => 'datetime',
        ];
    }

    public function product(): BelongsTo   { return $this->belongsTo(Product::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function user(): BelongsTo      { return $this->belongsTo(User::class); }
    public function company(): BelongsTo   { return $this->belongsTo(Company::class); }
}
