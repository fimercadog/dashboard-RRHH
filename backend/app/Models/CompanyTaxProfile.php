<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyTaxProfile extends Model
{
    protected $fillable = [
        'company_id', 'legal_nature', 'tax_contributor_type', 'parafiscal_exempt',
        'exempt_basis', 'valid_from', 'valid_until', 'created_by', 'updated_by',
        // verified_by, verified_at, review_status solo via /validate (forceFill)
    ];

    protected $casts = [
        'valid_from'    => 'date',
        'valid_until'   => 'date',
        'verified_at'   => 'date',
        'parafiscal_exempt' => 'boolean',
    ];

    public function company(): BelongsTo  { return $this->belongsTo(Company::class); }
    public function creator(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }

    /** Scope: perfiles válidos para una fecha dada. */
    public function scopeValidFor($query, \DateTimeInterface $date)
    {
        $d = \Carbon\Carbon::instance($date)->format('Y-m-d');
        return $query->where('valid_from', '<=', $d)
                     ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $d));
    }

    /** ¿Es este perfil válido para la fecha dada? */
    public function isValidFor(\DateTimeInterface $date): bool
    {
        $d = \Carbon\Carbon::instance($date);
        if ($this->valid_from->gt($d)) return false;
        if ($this->valid_until && $this->valid_until->lt($d)) return false;
        return true;
    }
}
