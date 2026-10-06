<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleOrder extends Model
{
    public const STATUSES = ['draft', 'confirmed', 'partial', 'fulfilled', 'cancelled'];

    protected $fillable = [
        'company_id', 'client_id', 'quote_id', 'user_id', 'warehouse_id',
        'number', 'date', 'status',
        'subtotal', 'discount_total', 'tax', 'total', 'notes',
    ];

    protected $casts = [
        'date'           => 'date',
        'subtotal'       => 'float',
        'discount_total' => 'float',
        'tax'            => 'float',
        'total'          => 'float',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function quote(): BelongsTo { return $this->belongsTo(Quote::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function items(): HasMany { return $this->hasMany(SaleOrderItem::class); }
    public function saleInvoices(): HasMany { return $this->hasMany(SaleInvoice::class); }
}
