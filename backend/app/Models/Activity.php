<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    public const TYPES = ['call', 'email', 'meeting', 'note', 'task'];
    public const STATUSES = ['pending', 'done', 'cancelled'];

    protected $fillable = [
        'company_id', 'user_id', 'deal_id', 'client_id', 'contact_id',
        'type', 'title', 'body', 'due_at', 'done_at', 'status',
    ];

    protected $casts = ['due_at' => 'datetime', 'done_at' => 'datetime'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function deal(): BelongsTo { return $this->belongsTo(Deal::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
}
