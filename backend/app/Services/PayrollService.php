<?php

namespace App\Services;

use App\Models\CompanyTaxProfile;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\PayrollItem;
use App\Models\PayrollItemLine;
use App\Models\PayrollNovelty;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function __construct(
        private readonly PayrollParameterService $params
    ) {}

    /**
     * Procesa la nómina de un período.
     * Si se especifica $employeeId, solo calcula para ese empleado.
     */
    public function process(PayrollPeriod $period, ?int $employeeId = null): PayrollRun
    {
        // Guard: período cerrado
        if ($period->status === 'closed') {
            abort(422, 'El período de nómina está cerrado. Use nómina complementaria o solicite autorización de reversión.');
        }

        $companyId  = $period->company_id;
        $startDate  = $period->start_date;
        $endDate    = $period->end_date;
        $workedDays = $startDate->diffInDays($endDate) + 1;
        // Nómina colombiana: 30 días por mes independiente del calendario
        $periodDays = 30;

        // Parámetros del período
        $smmlv     = $this->params->getValueAt('smmlv', $startDate, $companyId);
        $auxMonto  = $this->params->getValueAt('auxilio_transporte', $startDate, $companyId);
        $topeAux   = $this->params->getValueAt('tope_auxilio_transporte_smmlv', $startDate, $companyId) * $smmlv;

        $saludEmpPct     = $this->params->getValueAt('salud_empleado_pct', $startDate, $companyId) / 100;
        $saludEmpresaPct = $this->params->getValueAt('salud_empresa_pct', $startDate, $companyId) / 100;
        $pensEmpPct      = $this->params->getValueAt('pension_empleado_pct', $startDate, $companyId) / 100;
        $pensEmpresaPct  = $this->params->getValueAt('pension_empresa_pct', $startDate, $companyId) / 100;
        $ccfPct          = $this->params->getValueAt('caja_compensacion_pct', $startDate, $companyId) / 100;
        $senaPct         = $this->params->getValueAt('sena_pct', $startDate, $companyId) / 100;
        $icbfPct         = $this->params->getValueAt('icbf_pct', $startDate, $companyId) / 100;

        // UVT — puede no estar verificado (VP-005)
        $uvtVal   = $this->params->getParameterValue('uvt', $startDate, $companyId);
        $uvt      = $uvtVal?->value;
        $uvtVerif = $uvtVal && $uvtVal->verified_at !== null;

        // Perfil tributario de la empresa
        $taxProfile = CompanyTaxProfile::where('company_id', $companyId)
            ->where('review_status', 'validated')
            ->validFor($startDate)
            ->first();

        $snapshot = $this->params->getSnapshotForPeriod($startDate, $endDate, $companyId);

        return DB::transaction(function () use (
            $period, $companyId, $employeeId,
            $smmlv, $auxMonto, $topeAux,
            $saludEmpPct, $saludEmpresaPct, $pensEmpPct, $pensEmpresaPct,
            $ccfPct, $senaPct, $icbfPct,
            $uvt, $uvtVerif, $taxProfile, $snapshot, $periodDays
        ) {
            /** @var PayrollRun $run */
            $run = (new PayrollRun())->forceFill([
                'company_id'          => $companyId,
                'payroll_period_id'   => $period->id,
                'run_number'          => PayrollRun::where('payroll_period_id', $period->id)->count() + 1,
                'status'              => 'running',
                'parameters_snapshot' => $snapshot,
                'tax_profile_id'      => $taxProfile?->id,
                'executed_by'         => auth()->id(),
                'executed_at'         => now(),
            ]);
            $run->save();

            PayrollItem::where('payroll_run_id', $run->id)->delete();

            $errors     = [];
            $totalEmp   = 0;
            $totalDev   = 0;
            $totalDed   = 0;
            $totalNet   = 0;
            $totalEmpC  = 0;

            $contractQuery = EmployeeContract::where('company_id', $companyId)
                ->where('status', 'active')
                ->where('start_date', '<=', $period->end_date)
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $period->start_date));

            if ($employeeId) {
                $contractQuery->where('employee_id', $employeeId);
            }

            $contracts = $contractQuery->get();

            // Novedades del período (solo pending)
            $novelties = PayrollNovelty::where('company_id', $companyId)
                ->where('payroll_period_id', $period->id)
                ->where('status', 'pending')
                ->get()
                ->groupBy('employee_id');

            foreach ($contracts as $contract) {
                $emp = $contract->employee;

                // Salario base
                $salary = (float) $contract->salary;

                // IBC — salario integral: 70% del salario
                $ibc = ($contract->salary_modality === 'integral')
                    ? round($salary * 0.70, 2)
                    : $salary;

                // Clamp IBC al máximo (25 SMMLV)
                $ibcMax = $this->params->getParameterValue('ibc_maximo_smmlv', $period->start_date, $companyId);
                if ($ibcMax) {
                    $ibc = min($ibc, $ibcMax->value * $smmlv);
                }
                // Clamp IBC al mínimo (1 SMMLV)
                $ibc = max($ibc, $smmlv);

                // Días trabajados (MVP: mes completo = 30)
                $worked = $periodDays;

                // Salario ganado proporcional
                $salaryEarned = round($salary * $worked / $periodDays, 2);

                // Auxilio de transporte
                $transport = 0;
                if ($contract->salary_modality === 'ordinary' && $salary <= $topeAux) {
                    $transport = $auxMonto;
                }

                // Deducciones empleado
                $healthEmp   = round($ibc * $saludEmpPct, 2);
                $pensEmp     = round($ibc * $pensEmpPct, 2);
                $totalDeduc  = $healthEmp + $pensEmp;

                // Retención en la fuente (VP-005/VP-006)
                $withholding = 0;
                $retWarning  = null;
                if ($contract->salary_modality === 'integral') {
                    $retWarning = 'VP-006: retención no calculada para salario integral';
                    $errors[]   = $retWarning;
                } elseif (! $uvtVerif) {
                    $retWarning = 'VP-005: UVT sin verificar — retención omitida';
                    $errors[]   = $retWarning;
                } else {
                    $withholding = $this->calcWithholding($salaryEarned + $transport, $ibc, $uvt);
                }
                $totalDeduc += $withholding;

                // Novedades
                $noveltyEarnings  = 0;
                $noveltyDeductions = 0;
                $empNovs = $novelties->get($contract->employee_id, collect());
                foreach ($empNovs as $nov) {
                    if ($nov->amount >= 0) {
                        $noveltyEarnings += $nov->amount;
                    } else {
                        $noveltyDeductions += abs($nov->amount);
                    }
                }
                $totalDeduc += $noveltyDeductions;

                $totalEarned = $salaryEarned + $transport + $noveltyEarnings;
                $netPay      = $totalEarned - $totalDeduc;

                // Aportes empresa
                $healthEmpresa  = round($ibc * $saludEmpresaPct, 2);
                $pensEmpresa    = round($ibc * $pensEmpresaPct, 2);
                $ccf            = round($ibc * $ccfPct, 2);

                // ARL
                $arlPct = $this->getArlRate($contract->risk_class, $period->start_date, $companyId, $errors, $contract->employee_id);
                $arl    = round($ibc * $arlPct, 2);

                // Parafiscales
                $sena = 0;
                $icbf = 0;
                $parafiscalExempt = false;
                $parafiscalBasis  = null;

                if ($taxProfile && $taxProfile->review_status === 'validated') {
                    // Evaluar exoneración según Art. 114-1 ET
                    $is10Smmlv = $salary < (10 * $smmlv);
                    $isPjContrib = $taxProfile->parafiscal_exempt;

                    if ($isPjContrib && $is10Smmlv) {
                        $parafiscalExempt = true;
                        $parafiscalBasis  = 'Art. 114-1 ET — PJ contribuyente, salario < 10 SMMLV';
                    }
                } else {
                    // Sin perfil validado: no activar exoneración, registrar advertencia
                    $errors[] = "VP-002: company_tax_profile sin validar — SENA/ICBF calculados sin exoneración";
                }

                if (! $parafiscalExempt) {
                    $sena = round($ibc * $senaPct, 2);
                    $icbf = round($ibc * $icbfPct, 2);
                }

                // VP-004: Fondo de solidaridad pensional — deshabilitado, IBC > 4 SMMLV avisa
                if ($ibc > (4 * $smmlv)) {
                    $errors[] = "VP-004: FSP no calculado para empleado {$contract->employee_id} (IBC > 4 SMMLV)";
                }

                $totalEmployerCost = $healthEmpresa + $pensEmpresa + $arl + $ccf + $sena + $icbf;

                $item = PayrollItem::create([
                    'company_id'              => $companyId,
                    'payroll_run_id'          => $run->id,
                    'employee_id'             => $contract->employee_id,
                    'contract_id'             => $contract->id,
                    'salary_snapshot'         => $salary,
                    'salary_modality_snapshot' => $contract->salary_modality,
                    'ibc'                     => $ibc,
                    'worked_days'             => $worked,
                    'salary_earned'           => $salaryEarned,
                    'transport_allowance'     => $transport,
                    'bonuses'                 => $noveltyEarnings,
                    'other_earnings'          => 0,
                    'total_earned'            => $totalEarned,
                    'health_employee'         => $healthEmp,
                    'pension_employee'        => $pensEmp,
                    'withholding_tax'         => $withholding,
                    'total_deductions'        => $totalDeduc,
                    'net_pay'                 => $netPay,
                    'health_employer'         => $healthEmpresa,
                    'pension_employer'        => $pensEmpresa,
                    'arl_employer'            => $arl,
                    'ccf_employer'            => $ccf,
                    'sena_employer'           => $sena,
                    'icbf_employer'           => $icbf,
                    'parafiscal_exempt'       => $parafiscalExempt,
                    'parafiscal_exempt_basis' => $parafiscalBasis,
                    'total_employer_cost'     => $totalEmployerCost,
                ]);

                // Lines
                $this->createLines($item, $salary, $transport, $healthEmp, $pensEmp, $withholding,
                    $healthEmpresa, $pensEmpresa, $arl, $ccf, $sena, $icbf, $empNovs, $retWarning);

                // Marcar novedades como aplicadas
                foreach ($empNovs as $nov) {
                    $nov->update(['status' => 'applied', 'applied_run_id' => $run->id]);
                }

                $totalEmp++;
                $totalDev  += $totalEarned;
                $totalDed  += $totalDeduc;
                $totalNet  += $netPay;
                $totalEmpC += $totalEmployerCost;
            }

            $run->forceFill([
                'status'               => 'completed',
                'total_employees'      => $totalEmp,
                'total_devengado'      => $totalDev,
                'total_deducciones'    => $totalDed,
                'total_neto'           => $totalNet,
                'total_aportes_empresa' => $totalEmpC,
                'error_log'            => $errors ? implode("\n", $errors) : null,
            ])->save();

            $period->update(['status' => 'processing']);

            return $run;
        });
    }

    private function getArlRate(int $riskClass, \DateTimeInterface $date, int $companyId, array &$errors, int $employeeId): float
    {
        // Intenta tasa específica de empresa primero
        $code = "arl_clase_{$riskClass}_pct";
        try {
            return $this->params->getValueAt($code, $date, $companyId) / 100;
        } catch (\RuntimeException) {
            // VP-003: usar mínimo del rango
            $errors[] = "VP-003: tasa ARL clase {$riskClass} no configurada para empresa — usando tasa de referencia";
            try {
                return $this->params->getValueAt($code, $date) / 100;
            } catch (\RuntimeException) {
                $errors[] = "VP-003: tasa ARL clase {$riskClass} tampoco existe globalmente — ARL = 0";
                return 0.0;
            }
        }
    }

    /**
     * Retención en la fuente — Procedimiento 1 (Art. 385 ET).
     * Solo para salario ordinario con UVT verificado.
     */
    private function calcWithholding(float $grossIncome, float $ibc, float $uvt): float
    {
        // PASO 1: Ingresos brutos
        // PASO 2: Deducciones obligatorias (salud + pensión empleado = 8% del IBC)
        $mandatoryDeductions = $ibc * 0.08;

        // PASO 3: Ingresos netos
        $netIncome = $grossIncome - $mandatoryDeductions;

        // PASO 4: Sin deducciones opcionales en el MVP
        $subtotal = $netIncome;

        // PASO 6: Renta exenta 25%
        $exentaTope = 240 * $uvt; // tope mensual
        $exenta = min($subtotal * 0.25, $exentaTope);

        // PASO 7: Base gravable
        $baseGravable = max(0, $subtotal - $exenta);

        // PASO 8: Aplicar tabla Art. 383 ET en UVT
        $baseUvt = $baseGravable / $uvt;

        $retUvt = match (true) {
            $baseUvt <= 95          => 0,
            $baseUvt <= 150         => ($baseUvt - 95) * 0.19,
            $baseUvt <= 360         => ($baseUvt - 150) * 0.28 + 10.5,
            $baseUvt <= 640         => ($baseUvt - 360) * 0.33 + 69.3,
            $baseUvt <= 945         => ($baseUvt - 640) * 0.35 + 162.1,
            $baseUvt <= 2300        => ($baseUvt - 945) * 0.37 + 268.9,
            default                 => ($baseUvt - 2300) * 0.39 + 770.0,
        };

        return round($retUvt * $uvt, 2);
    }

    private function createLines(
        PayrollItem $item,
        float $salary,
        float $transport,
        float $healthEmp,
        float $pensEmp,
        float $withholding,
        float $healthEmpresa,
        float $pensEmpresa,
        float $arl,
        float $ccf,
        float $sena,
        float $icbf,
        $novelties,
        ?string $retWarning
    ): void {
        $lines = [
            ['earning',               'salario_base',        'Salario base',                   null, null, $salary],
        ];

        if ($transport > 0) {
            $lines[] = ['earning', 'auxilio_transporte', 'Auxilio de transporte', null, null, $transport];
        }

        foreach ($novelties as $nov) {
            $type = $nov->amount >= 0 ? 'earning' : 'deduction';
            $lines[] = [$type, $nov->novelty_type, $nov->description, null, null, abs($nov->amount)];
        }

        $lines[] = ['deduction', 'salud_empleado',  'Salud empleado (4%)',   null, null, $healthEmp];
        $lines[] = ['deduction', 'pension_empleado', 'Pensión empleado (4%)', null, null, $pensEmp];

        if ($withholding > 0) {
            $lines[] = ['deduction', 'retencion_fuente', 'Retención en la fuente', null, null, $withholding];
        }

        $lines[] = ['employer_contribution', 'salud_empresa',   'Salud empresa (8.5%)', null, null, $healthEmpresa];
        $lines[] = ['employer_contribution', 'pension_empresa', 'Pensión empresa (12%)', null, null, $pensEmpresa];
        $lines[] = ['employer_contribution', 'arl',             'ARL',                  null, null, $arl];
        $lines[] = ['employer_contribution', 'caja_compensacion', 'Caja de compensación (4%)', null, null, $ccf];

        if ($sena > 0) {
            $lines[] = ['employer_contribution', 'sena', 'SENA (2%)', null, null, $sena];
        }
        if ($icbf > 0) {
            $lines[] = ['employer_contribution', 'icbf', 'ICBF (3%)', null, null, $icbf];
        }

        foreach ($lines as [$type, $code, $desc, $qty, $unitVal, $amount]) {
            PayrollItemLine::create([
                'payroll_item_id' => $item->id,
                'line_type'       => $type,
                'code'            => $code,
                'description'     => $desc,
                'quantity'        => $qty,
                'unit_value'      => $unitVal,
                'amount'          => $amount,
                'created_at'      => now(),
            ]);
        }
    }
}
