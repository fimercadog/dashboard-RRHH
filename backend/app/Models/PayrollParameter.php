<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollParameter extends Model
{
    protected $fillable = ['code', 'name', 'description', 'scope', 'company_id'];

    public function company(): BelongsTo    { return $this->belongsTo(Company::class); }
    public function values(): HasMany       { return $this->hasMany(PayrollParameterValue::class, 'parameter_id'); }

    /**
     * Valor vigente para una fecha dada.
     */
    public function valueFor(\DateTimeInterface $date): ?PayrollParameterValue
    {
        $d = \Carbon\Carbon::instance($date)->format('Y-m-d');

        return $this->values()
            ->where('effective_from', '<=', $d)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $d))
            ->orderByDesc('effective_from')
            ->first();
    }
}
