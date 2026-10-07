<?php

namespace Database\Seeders;

use App\Models\AccountingAccountConfig;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Siembra un plan de cuentas básico funcional para Colombia.
 * ⚠️ NO es el PUC oficial (Decreto 2649/1993) — estructura funcional de referencia.
 * VALIDAR NORMATIVAMENTE antes de usar en producción con obligaciones legales.
 */
class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        Company::all()->each(fn ($company) => $this->seedForCompany($company->id));
    }

    private function seedPeriodAndEntries(int $companyId, array $codeToId): void
    {
        $admin = User::where('email', 'admin@andespeople.co')->first();

        if (! $admin) {
            return;
        }

        $period = AccountingPeriod::firstOrCreate(
            ['company_id' => $companyId, 'name' => 'Ejercicio 2025'],
            [
                'start_date' => Carbon::create(2025, 1, 1),
                'end_date'   => Carbon::create(2025, 12, 31),
                'status'     => 'closed',
            ]
        );

        $entry = JournalEntry::firstOrCreate(
            ['company_id' => $companyId, 'number' => 'CE-2025-0001'],
            [
                'accounting_period_id' => $period->id,
                'date'                 => Carbon::create(2025, 1, 1),
                'description'          => 'Comprobante de apertura — saldos iniciales demo.',
                'status'               => 'posted',
                'user_id'              => $admin->id,
            ]
        );

        if ($entry->wasRecentlyCreated) {
            $lines = [
                // Debitos (activos)
                [$codeToId['1105'] ?? null, 'Saldo inicial caja',          5_500_000,         0, 1],
                [$codeToId['1110'] ?? null, 'Saldo inicial bancos',      142_350_000,         0, 2],
                [$codeToId['1115'] ?? null, 'Saldo inicial ahorro',       38_000_000,         0, 3],
                [$codeToId['1435'] ?? null, 'Inventario inicial mercancia', 32_105_000,        0, 4],
                // Credito (patrimonio)
                [$codeToId['3105'] ?? null, 'Capital suscrito apertura',  0, 218_955_000, 5],
            ];

            foreach ($lines as [$accountId, $desc, $debit, $credit, $seq]) {
                if ($accountId) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $entry->id,
                        'account_id'       => $accountId,
                        'description'      => $desc,
                        'debit'            => $debit,
                        'credit'           => $credit,
                        'sequence'         => $seq,
                    ]);
                }
            }
        }
    }

    private function seedForCompany(int $companyId): void
    {
        $tree = [
            // ACTIVOS
            ['1', 'ACTIVO', 'asset', 'debit', null, 1, false],
            ['11', 'Disponible', 'asset', 'debit', '1', 2, false],
            ['1105', 'Caja', 'asset', 'debit', '11', 3, true],
            ['1110', 'Bancos', 'asset', 'debit', '11', 3, true],
            ['1115', 'Cuentas de ahorro', 'asset', 'debit', '11', 3, true],
            ['13', 'Deudores', 'asset', 'debit', '1', 2, false],
            ['1305', 'Clientes (CxC)', 'asset', 'debit', '13', 3, true],
            ['14', 'Inventarios', 'asset', 'debit', '1', 2, false],
            ['1435', 'Mercancías', 'asset', 'debit', '14', 3, true],
            // PASIVOS
            ['2', 'PASIVO', 'liability', 'credit', null, 1, false],
            ['22', 'Proveedores', 'liability', 'credit', '2', 2, false],
            ['2205', 'Proveedores nacionales (CxP)', 'liability', 'credit', '22', 3, true],
            ['24', 'Impuestos, gravámenes y tasas', 'liability', 'credit', '2', 2, false],
            ['2408', 'IVA por pagar', 'liability', 'credit', '24', 3, true],
            // PATRIMONIO
            ['3', 'PATRIMONIO', 'equity', 'credit', null, 1, false],
            ['31', 'Capital social', 'equity', 'credit', '3', 2, false],
            ['3105', 'Capital suscrito', 'equity', 'credit', '31', 3, true],
            ['36', 'Resultados del ejercicio', 'equity', 'credit', '3', 2, false],
            ['3605', 'Utilidad del ejercicio', 'equity', 'credit', '36', 3, true],
            ['3610', 'Pérdida del ejercicio', 'equity', 'debit', '36', 3, true],
            ['37', 'Resultados de ejercicios anteriores', 'equity', 'credit', '3', 2, false],
            ['3705', 'Utilidades acumuladas', 'equity', 'credit', '37', 3, true],
            // INGRESOS
            ['4', 'INGRESOS', 'revenue', 'credit', null, 1, false],
            ['41', 'Ingresos operacionales', 'revenue', 'credit', '4', 2, false],
            ['4135', 'Comercio al por mayor y al por menor', 'revenue', 'credit', '41', 3, true],
            ['4155', 'Servicios', 'revenue', 'credit', '41', 3, true],
            // GASTOS
            ['5', 'GASTOS', 'expense', 'debit', null, 1, false],
            ['51', 'Gastos operacionales de administración', 'expense', 'debit', '5', 2, false],
            ['5105', 'Gastos de personal', 'expense', 'debit', '51', 3, true],
            ['5195', 'Diversos', 'expense', 'debit', '51', 3, true],
            // COSTOS
            ['6', 'COSTOS', 'cost', 'debit', null, 1, false],
            ['61', 'Costo de ventas', 'cost', 'debit', '6', 2, false],
            ['6135', 'Costo de ventas — mercancías', 'cost', 'debit', '61', 3, true],
        ];

        // Primero pasada: crear todas las cuentas sin parent (para que los IDs existan)
        $codeToId = [];
        foreach ($tree as [$code, $name, $type, $nature, $parentCode, $level, $allows]) {
            $account = ChartOfAccount::firstOrCreate(
                ['company_id' => $companyId, 'code' => $code],
                [
                    'name'             => $name,
                    'type'             => $type,
                    'nature'           => $nature,
                    'level'            => $level,
                    'allows_movements' => $allows,
                    'status'           => 'active',
                ]
            );
            $codeToId[$code] = $account->id;
        }

        // Segunda pasada: asignar parent_id
        foreach ($tree as [$code, , , , $parentCode]) {
            if ($parentCode) {
                ChartOfAccount::where('company_id', $companyId)
                    ->where('code', $code)
                    ->update(['parent_id' => $codeToId[$parentCode]]);
            }
        }

        // Configuración de cuentas de integración por defecto
        $defaults = [
            'cxc_default'      => '1305',
            'cxp_default'      => '2205',
            'inventory_default'=> '1435',
            'cogs_default'     => '6135',
            'revenue_default'  => '4135',
            'net_income'       => '3605',
            'retained_earnings'=> '3705',
        ];

        foreach ($defaults as $key => $code) {
            $accountId = $codeToId[$code] ?? null;
            if ($accountId) {
                AccountingAccountConfig::updateOrCreate(
                    ['company_id' => $companyId, 'config_key' => $key],
                    ['account_id' => $accountId]
                );
            }
        }

        $this->seedPeriodAndEntries($companyId, $codeToId);
    }
}
