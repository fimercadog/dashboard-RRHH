<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Segment extends Model
{
    protected $fillable = ['company_id', 'name', 'description'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function clients(): BelongsToMany { return $this->belongsToMany(Client::class, 'segment_clients'); }
}
