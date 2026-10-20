<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollParameterValue extends Model
{
    protected $fillable = [
        'parameter_id', 'effective_from', 'effective_to', 'value', 'unit',
        'source_norm', 'source_url', 'verified_at', 'verified_by', 'notes', 'created_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to'   => 'date',
        'verified_at'    => 'date',
        'value'          => 'float',
    ];

    public function parameter(): BelongsTo  { return $this->belongsTo(PayrollParameter::class); }
    public function verifiedBy(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }

    /**
     * Inmutabilidad: no se actualiza; solo se crean nuevos registros.
     * Este método lanza excepción si se intenta actualizar un valor existente.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \LogicException('PayrollParameterValue es inmutable. Cree un nuevo registro con nueva vigencia.');
    }
}
