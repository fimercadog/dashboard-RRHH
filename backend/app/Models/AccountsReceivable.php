<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountsReceivable extends Model
{
    protected $table = 'accounts_receivable';

    public const STATUSES = ['open', 'partially_paid', 'paid', 'cancelled'];

    protected $fillable = [
        'company_id', 'client_id', 'sale_invoice_id',
        'amount', 'balance', 'due_date', 'status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount'   => 'float',
        'balance'  => 'float',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function saleInvoice(): BelongsTo { return $this->belongsTo(SaleInvoice::class); }
}
