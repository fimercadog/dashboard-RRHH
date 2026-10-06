<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deal extends Model
{
    use SoftDeletes;

    public const STAGES = [
        'prospecting', 'qualification', 'proposal', 'negotiation', 'won', 'lost', 'stalled',
    ];

    protected $fillable = [
        'company_id', 'client_id', 'owner_id', 'title', 'amount', 'stage',
        'expected_close_date', 'notes', 'sale_order_id',
    ];

    protected $casts = ['amount' => 'float'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function activities(): HasMany { return $this->hasMany(Activity::class); }
    public function saleOrder(): BelongsTo { return $this->belongsTo(SaleOrder::class); }
}
