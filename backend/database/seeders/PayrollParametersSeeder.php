<?php

namespace Database\Seeders;

use App\Models\PayrollParameter;
use App\Models\PayrollParameterValue;
use Illuminate\Database\Seeder;

class PayrollParametersSeeder extends Seeder
{
    public function run(): void
    {
        $params = [
            // ─── Valores legales COP 2026 ─────────────────────────────────────
            ['smmlv',              'Salario mínimo mensual legal vigente', 'global', 'COP', 1750905,  '2026-01-01', 'Decreto 159 de 2026, Art. 1',                   '2026-10-20'],
            ['auxilio_transporte', 'Auxilio de transporte',                'global', 'COP',  249095, '2026-01-01', 'Decreto 1470 de 2025, Art. 1',                  '2026-10-20'],
            ['uvt',                'Unidad de Valor Tributario',           'global', 'COP',   52374, '2026-01-01', 'Resolución DIAN 000238 de 2025',                '2026-10-20'],

            // ─── Tasas porcentuales ───────────────────────────────────────────
            ['salud_empleado_pct',   'Aporte salud empleado',     'global', 'percent',  4.00, '2026-01-01', 'Art. 204 Ley 100/1993',      '2026-10-20'],
            ['salud_empresa_pct',    'Aporte salud empleador',    'global', 'percent',  8.50, '2026-01-01', 'Art. 204 Ley 100/1993',      '2026-10-20'],
            ['pension_empleado_pct', 'Aporte pensión empleado',   'global', 'percent',  4.00, '2026-01-01', 'Art. 7 Ley 797/2003',        '2026-10-20'],
            ['pension_empresa_pct',  'Aporte pensión empleador',  'global', 'percent', 12.00, '2026-01-01', 'Art. 7 Ley 797/2003',        '2026-10-20'],
            ['sena_pct',             'Aportes SENA',              'global', 'percent',  2.00, '2026-01-01', 'Ley 21/1982',                '2026-10-20'],
            ['icbf_pct',             'Aportes ICBF',              'global', 'percent',  3.00, '2026-01-01', 'Ley 21/1982',                '2026-10-20'],
            ['caja_compensacion_pct','Caja de compensación familiar','global','percent', 4.00, '2026-01-01', 'Ley 21/1982',               '2026-10-20'],

            // ─── ARL por clase de riesgo (tasas de referencia mínima ATEP) ───
            ['arl_clase_1_pct', 'ARL clase de riesgo I',   'global', 'percent', 0.522, '2026-01-01', 'Decreto 1607/2002, Tabla ATEP', '2026-10-20'],
            ['arl_clase_2_pct', 'ARL clase de riesgo II',  'global', 'percent', 1.044, '2026-01-01', 'Decreto 1607/2002, Tabla ATEP', '2026-10-20'],
            ['arl_clase_3_pct', 'ARL clase de riesgo III', 'global', 'percent', 2.436, '2026-01-01', 'Decreto 1607/2002, Tabla ATEP', '2026-10-20'],
            ['arl_clase_4_pct', 'ARL clase de riesgo IV',  'global', 'percent', 4.350, '2026-01-01', 'Decreto 1607/2002, Tabla ATEP', '2026-10-20'],
            ['arl_clase_5_pct', 'ARL clase de riesgo V',   'global', 'percent', 6.960, '2026-01-01', 'Decreto 1607/2002, Tabla ATEP', '2026-10-20'],

            // ─── Umbrales SMMLV ───────────────────────────────────────────────
            ['tope_auxilio_transporte_smmlv', 'Tope salarial auxilio transporte', 'global', 'SMMLV', 2.00, '2026-01-01', 'Ley 15/1959', '2026-10-20'],
            ['ibc_maximo_smmlv',             'IBC máximo (25 SMMLV)',             'global', 'SMMLV', 25.00, '2026-01-01', 'Art. 5 Ley 797/2003', '2026-10-20'],
            ['fondo_solidaridad_umbral_smmlv','IBC mínimo fondo solidaridad',     'global', 'SMMLV', 4.00, '2026-01-01', 'Art. 27 Ley 100/1993', '2026-10-20'],

            // ─── Retención en la fuente ───────────────────────────────────────
            ['retefuente_renta_exenta_pct',      'Renta exenta empleados (25%)', 'global', 'percent', 25.00, '2026-01-01', 'Numeral 10 Art. 206 ET', '2026-10-20'],
            ['retefuente_renta_exenta_tope_uvt', 'Tope mensual renta exenta',    'global', 'UVT',    240.00, '2026-01-01', 'Numeral 10 Art. 206 ET', '2026-10-20'],
        ];

        foreach ($params as [$code, $name, $scope, $unit, $value, $effectiveFrom, $sourceNorm, $verifiedAt]) {
            $param = PayrollParameter::firstOrCreate(
                ['code' => $code, 'company_id' => null],
                ['name' => $name, 'scope' => $scope, 'description' => null]
            );

            // Solo crear si no existe ya un valor para esa fecha
            $exists = PayrollParameterValue::where('parameter_id', $param->id)
                ->where('effective_from', $effectiveFrom)
                ->exists();

            if (! $exists) {
                PayrollParameterValue::create([
                    'parameter_id'   => $param->id,
                    'effective_from' => $effectiveFrom,
                    'effective_to'   => null,
                    'value'          => $value,
                    'unit'           => $unit,
                    'source_norm'    => $sourceNorm,
                    'verified_at'    => $verifiedAt,
                ]);
            }
        }
    }
}
