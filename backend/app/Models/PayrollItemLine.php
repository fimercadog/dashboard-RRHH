<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItemLine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'payroll_item_id', 'line_type', 'code', 'description',
        'quantity', 'unit_value', 'amount', 'source_type', 'source_id', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function item(): BelongsTo { return $this->belongsTo(PayrollItem::class, 'payroll_item_id'); }
}
