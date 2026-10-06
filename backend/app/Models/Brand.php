<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Brand extends Model
{
    protected $fillable = ['company_id', 'name', 'status'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
}
