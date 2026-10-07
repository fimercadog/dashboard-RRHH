<?php

namespace Database\Seeders;

use App\Models\AccountPayable;
use App\Models\AccountsReceivable;
use App\Models\CashAccount;
use App\Models\Company;
use App\Models\FinancialTransaction;
use App\Models\Payment;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class FinanceSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $admin   = User::where('email', 'admin@andespeople.co')->first();

        if (! $company || ! $admin) {
            return;
        }

        // ── Cuentas de efectivo/banco ─────────────────────────────────────────
        $caja = CashAccount::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Caja General'],
            ['type' => 'cash', 'currency' => 'COP', 'balance' => 5_500_000, 'status' => 'active']
        );
        $bancoPrincipal = CashAccount::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Bancolombia — Cta. Corriente'],
            ['type' => 'bank', 'currency' => 'COP', 'balance' => 142_350_000, 'status' => 'active', 'notes' => 'Cuenta principal de operaciones.']
        );
        $bancoAhorro = CashAccount::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Davivienda — Cta. Ahorro'],
            ['type' => 'savings', 'currency' => 'COP', 'balance' => 38_000_000, 'status' => 'active', 'notes' => 'Fondo de reserva trimestral.']
        );

        // ── Transacciones financieras ─────────────────────────────────────────
        $txDefs = [
            [$bancoPrincipal->id, 'income',   69_020_000, 'Cobro FEV-2026-0001 — Grupo Andino',          -20],
            [$bancoPrincipal->id, 'income',    4_250_000, 'Abono parcial FEV-2026-0002 — H. Giraldo',    -12],
            [$bancoPrincipal->id, 'expense',   3_800_000, 'Pago nomina administrativa (octubre)',          -5],
            [$caja->id,           'income',    1_200_000, 'Venta efectivo — servicios menores',            -8],
            [$caja->id,           'expense',     450_000, 'Compra suministros papeleria',                  -3],
            [$bancoPrincipal->id, 'expense',  26_775_000, 'Abono FCP-2026-0001 — Tech Distribuciones',    -1],
        ];

        foreach ($txDefs as [$accountId, $type, $amount, $desc, $days]) {
            FinancialTransaction::firstOrCreate(
                ['company_id' => $company->id, 'cash_account_id' => $accountId, 'description' => $desc],
                [
                    'user_id'        => $admin->id,
                    'type'           => $type,
                    'amount'         => $amount,
                    'date'           => Carbon::today()->addDays($days),
                    'reference_type' => 'manual',
                    'reference_id'   => 0,
                ]
            );
        }

        // ── Pagos demo ────────────────────────────────────────────────────────
        $ar1 = AccountsReceivable::where('company_id', $company->id)->first();
        $ar2 = AccountsReceivable::where('company_id', $company->id)->skip(1)->first();
        $ap1 = AccountPayable::where('company_id', $company->id)->first();

        if ($ar1) {
            Payment::firstOrCreate(
                ['company_id' => $company->id, 'reference' => 'PAY-2026-0001'],
                [
                    'cash_account_id' => $bancoPrincipal->id,
                    'user_id'         => $admin->id,
                    'payable_type'    => AccountsReceivable::class,
                    'payable_id'      => $ar1->id,
                    'amount'          => 69_020_000,
                    'date'            => Carbon::today()->subDays(20),
                    'method'          => 'transfer',
                    'notes'           => 'Cobro total factura Grupo Andino.',
                    'status'          => 'active',
                ]
            );
        }
        if ($ar2) {
            Payment::firstOrCreate(
                ['company_id' => $company->id, 'reference' => 'PAY-2026-0002'],
                [
                    'cash_account_id' => $bancoPrincipal->id,
                    'user_id'         => $admin->id,
                    'payable_type'    => AccountsReceivable::class,
                    'payable_id'      => $ar2->id,
                    'amount'          => 4_250_000,
                    'date'            => Carbon::today()->subDays(12),
                    'method'          => 'transfer',
                    'notes'           => 'Abono parcial H. Giraldo.',
                    'status'          => 'active',
                ]
            );
        }
        if ($ap1) {
            Payment::firstOrCreate(
                ['company_id' => $company->id, 'reference' => 'PAY-2026-0003'],
                [
                    'cash_account_id' => $bancoPrincipal->id,
                    'user_id'         => $admin->id,
                    'payable_type'    => AccountPayable::class,
                    'payable_id'      => $ap1->id,
                    'amount'          => 26_775_000,
                    'date'            => Carbon::today()->subDays(1),
                    'method'          => 'transfer',
                    'notes'           => 'Abono CxP Tech Distribuciones.',
                    'status'          => 'active',
                ]
            );
        }

        // ── Transferencia entre cuentas ───────────────────────────────────────
        Transfer::firstOrCreate(
            ['company_id' => $company->id, 'reference' => 'TRF-2026-0001'],
            [
                'from_account_id' => $bancoPrincipal->id,
                'to_account_id'   => $bancoAhorro->id,
                'user_id'         => $admin->id,
                'amount'          => 15_000_000,
                'date'            => Carbon::today()->subDays(15),
                'notes'           => 'Traslado a reserva trimestral.',
                'status'          => 'active',
            ]
        );
    }
}
