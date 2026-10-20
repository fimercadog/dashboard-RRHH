<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    protected $fillable = [
        'company_id', 'name', 'period_type', 'start_date', 'end_date', 'payment_date',
        'status', 'is_complementary', 'original_period_id', 'authorized_reversal',
        'reversal_reason', 'reversal_authorized_by', 'reversal_authorized_at',
        'closed_by', 'closed_at', 'notes',
    ];

    protected static function booted(): void
    {
        // Garantiza que cualquier create() via API externa arranque en draft,
        // independientemente del payload recibido.
        static::creating(fn ($m) => $m->status = 'draft');
    }

    protected $casts = [
        'start_date'              => 'date',
        'end_date'                => 'date',
        'payment_date'            => 'date',
        'closed_at'               => 'datetime',
        'reversal_authorized_at'  => 'datetime',
        'is_complementary'        => 'boolean',
        'authorized_reversal'     => 'boolean',
    ];

    public function company(): BelongsTo       { return $this->belongsTo(Company::class); }
    public function originalPeriod(): BelongsTo { return $this->belongsTo(PayrollPeriod::class, 'original_period_id'); }
    public function runs(): HasMany            { return $this->hasMany(PayrollRun::class, 'payroll_period_id'); }
    public function novelties(): HasMany       { return $this->hasMany(PayrollNovelty::class); }

    /**
     * CA-N: verifica que se pueden satisfacer todos los requisitos de cierre.
     * Devuelve array vacío si puede cerrar, o array de strings con lo que falta.
     */
    public function canClose(): array
    {
        $missing = [];
        $paramSvc = app(\App\Services\PayrollParameterService::class);
        $companyId = $this->company_id;
        $date = $this->start_date;

        // (a) SMMLV vigente con verified_at no nulo
        $smmlv = $paramSvc->getParameterValue('smmlv', $date, $companyId);
        if (! $smmlv || ! $smmlv->verified_at) {
            $missing[] = 'smmlv_not_verified';
        }

        // (b) Auxilio de transporte vigente con verified_at no nulo
        $aux = $paramSvc->getParameterValue('auxilio_transporte', $date, $companyId);
        if (! $aux || ! $aux->verified_at) {
            $missing[] = 'auxilio_transporte_not_verified';
        }

        // (c) UVT vigente con verified_at no nulo
        $uvt = $paramSvc->getParameterValue('uvt', $date, $companyId);
        if (! $uvt || ! $uvt->verified_at) {
            $missing[] = 'uvt_not_verified';
        }

        // (e) company_tax_profile validado vigente para el período
        $profile = CompanyTaxProfile::where('company_id', $companyId)
            ->where('review_status', 'validated')
            ->validFor($date)
            ->first();

        if (! $profile) {
            $missing[] = 'company_tax_profile_not_validated';
        }

        return $missing;
    }
}
