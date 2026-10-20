<?php

namespace App\Services;

use App\Models\PayrollParameter;
use App\Models\PayrollParameterValue;
use Carbon\Carbon;

class PayrollParameterService
{
    /**
     * Devuelve el objeto PayrollParameterValue vigente para la fecha y empresa dadas.
     * Busca primero scope='company' para el companyId dado; si no hay, cae al global.
     */
    public function getParameterValue(string $code, \DateTimeInterface $date, ?int $companyId = null): ?PayrollParameterValue
    {
        $d = Carbon::instance($date)->format('Y-m-d');

        // Scope empresa primero
        if ($companyId) {
            $param = PayrollParameter::where('code', $code)
                ->where('scope', 'company')
                ->where('company_id', $companyId)
                ->first();

            if ($param) {
                $val = $this->valueForParam($param->id, $d);
                if ($val) return $val;
            }
        }

        // Fallback global
        $param = PayrollParameter::where('code', $code)
            ->where('scope', 'global')
            ->whereNull('company_id')
            ->first();

        return $param ? $this->valueForParam($param->id, $d) : null;
    }

    /**
     * Devuelve el valor numérico (float) del parámetro para la fecha y empresa dadas.
     * Lanza excepción si no existe — los callers deben llamar a getParameterValue si quieren null.
     */
    public function getValueAt(string $code, \DateTimeInterface $date, ?int $companyId = null): float
    {
        $val = $this->getParameterValue($code, $date, $companyId);
        if (! $val) {
            throw new \RuntimeException("Parámetro de nómina '{$code}' no encontrado para la fecha " . Carbon::instance($date)->format('Y-m-d'));
        }
        return $val->value;
    }

    /**
     * Snapshot de todos los parámetros vigentes durante un período.
     * Incluye source_norm, verified_at y valor.
     */
    public function getSnapshotForPeriod(\DateTimeInterface $from, \DateTimeInterface $to, ?int $companyId = null): array
    {
        $params = PayrollParameter::where(fn ($q) =>
            $q->where('scope', 'global')->whereNull('company_id')
        )->when($companyId, fn ($q) =>
            $q->orWhere(fn ($sq) =>
                $sq->where('scope', 'company')->where('company_id', $companyId)
            )
        )->get();

        $snapshot = [];
        $d = Carbon::instance($from)->format('Y-m-d');

        foreach ($params as $param) {
            $val = $this->valueForParam($param->id, $d);
            if ($val) {
                $snapshot[$param->code] = [
                    'parameter_id'   => $param->id,
                    'code'           => $param->code,
                    'name'           => $param->name,
                    'value'          => $val->value,
                    'unit'           => $val->unit,
                    'source_norm'    => $val->source_norm,
                    'effective_from' => $val->effective_from?->format('Y-m-d'),
                    'effective_to'   => $val->effective_to?->format('Y-m-d'),
                    'verified_at'    => $val->verified_at?->format('Y-m-d'),
                ];
            }
        }

        return $snapshot;
    }

    private function valueForParam(int $parameterId, string $date): ?PayrollParameterValue
    {
        return PayrollParameterValue::where('parameter_id', $parameterId)
            ->where('effective_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
            ->orderByDesc('effective_from')
            ->first();
    }
}
