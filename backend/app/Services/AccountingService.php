<?php

namespace App\Services;

use App\Models\AccountingAccountConfig;
use App\Models\AccountingPeriod;
use App\Models\CashAccount;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\SaleInvoice;
use App\Models\StockMovement;
use App\Models\Transfer;
use Illuminate\Support\Facades\DB;

/**
 * AccountingService es el ÚNICO escritor de journal_entries y journal_entry_lines.
 * No escribe: cash_accounts.balance, stock, CxC, CxP.
 */
class AccountingService
{
    /**
     * Genera y contabiliza un asiento desde un documento origen.
     * Si no hay período abierto para la fecha, retorna null silenciosamente
     * (la operación comercial ya se realizó; no bloquearla por falta de config contable).
     * Si hay período pero las cuentas no están configuradas, lanza excepción.
     */
    public function generateAndPost(
        string $referenceType,
        int    $referenceId,
        int    $companyId,
        int    $userId
    ): ?JournalEntry {
        $date  = $this->resolveDocumentDate($referenceType, $referenceId);
        $period = $this->findOpenPeriod($companyId, $date);

        if (! $period) {
            return null; // contabilidad no configurada, omitir sin error
        }

        // Idempotencia: si ya existe un asiento para este documento, no duplicar.
        $existing = JournalEntry::where('company_id', $companyId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->whereIn('status', ['draft', 'posted'])
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($referenceType, $referenceId, $companyId, $userId, $date, $period) {
            $lines = $this->buildLines($referenceType, $referenceId, $companyId);

            if (empty($lines)) {
                return null;
            }

            $entry = $this->createEntry($companyId, $period, $date, $referenceType, $referenceId, $userId, $lines);
            $this->postEntry($entry, $userId);
            $this->fillHooks($referenceType, $referenceId, $entry);

            return $entry;
        });
    }

    /**
     * Contabiliza un asiento en estado draft → posted.
     * Valida partida doble antes de postear.
     */
    public function postEntry(JournalEntry $entry, int $userId): void
    {
        if ($entry->status !== 'draft') {
            throw new \LogicException("Solo se pueden postear asientos en estado draft (actual: {$entry->status}).");
        }

        $entry->loadMissing('lines.account');

        $sumDebit  = $entry->lines->sum('debit');
        $sumCredit = $entry->lines->sum('credit');

        if (round(abs($sumDebit - $sumCredit), 4) > 0.001) {
            throw new \LogicException(
                "El asiento no cuadra: debe={$sumDebit}, haber={$sumCredit}."
            );
        }

        foreach ($entry->lines as $line) {
            if (! $line->account->isMovable()) {
                throw new \LogicException(
                    "La cuenta '{$line->account->code} {$line->account->name}' no admite movimientos o está inactiva."
                );
            }
        }

        $entry->update(['status' => 'posted']);
    }

    /**
     * Crea un asiento de reversión en el período actual abierto.
     * El asiento original queda como 'reversed' — nunca se elimina.
     * Si el original está en período cerrado, la reversión va en el período actual.
     */
    public function reverseEntry(JournalEntry $original, int $userId): JournalEntry
    {
        if ($original->status !== 'posted') {
            throw new \LogicException("Solo se pueden revertir asientos posted (actual: {$original->status}).");
        }

        if ($original->reversed_by_entry_id) {
            throw new \LogicException("Este asiento ya fue revertido.");
        }

        $today  = now()->toDateString();
        $period = $this->findOpenPeriod($original->company_id, $today);

        if (! $period) {
            throw new \LogicException("No hay un período contable abierto para registrar la reversión.");
        }

        return DB::transaction(function () use ($original, $period, $today, $userId) {
            $original->loadMissing('lines');

            $reversalLines = $original->lines->map(fn ($l) => [
                'account_id'  => $l->account_id,
                'description' => "Rev. {$l->description}",
                'debit'       => $l->credit,  // invertido
                'credit'      => $l->debit,   // invertido
                'sequence'    => $l->sequence,
            ])->all();

            $reversal = $this->createEntry(
                $original->company_id,
                $period,
                $today,
                'reversal',
                $original->id,
                $userId,
                $reversalLines
            );
            $reversal->update(['reversal_of_entry_id' => $original->id]);

            $this->postEntry($reversal, $userId);

            $original->update(['status' => 'reversed', 'reversed_by_entry_id' => $reversal->id]);

            // Limpia el gancho del documento origen para que pueda re-contabilizarse (solo si tiene referencia)
            if ($original->reference_type) {
                $this->clearHooks($original->reference_type, (int) $original->reference_id, $original->id);
            }

            return $reversal;
        });
    }

    /**
     * Abre un período contable.
     * Rechaza si las fechas se solapan con un período existente de la empresa.
     */
    public function openPeriod(
        int    $companyId,
        string $name,
        string $startDate,
        string $endDate,
        int    $userId
    ): AccountingPeriod {
        $overlap = AccountingPeriod::where('company_id', $companyId)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(fn ($q2) => $q2->where('start_date', '<=', $startDate)->where('end_date', '>=', $endDate));
            })
            ->exists();

        if ($overlap) {
            throw new \LogicException("Las fechas se solapan con un período contable existente.");
        }

        return AccountingPeriod::create([
            'company_id' => $companyId,
            'name'       => $name,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'status'     => 'open',
        ]);
    }

    /**
     * Cierra un período contable y genera los asientos de cierre.
     * Solo si no hay asientos draft pendientes en el período.
     */
    public function closePeriod(AccountingPeriod $period, int $userId): void
    {
        if ($period->status !== 'open') {
            throw new \LogicException("El período ya está cerrado.");
        }

        $drafts = JournalEntry::where('accounting_period_id', $period->id)
            ->where('status', 'draft')
            ->count();

        if ($drafts > 0) {
            throw new \LogicException("Hay {$drafts} asiento(s) en draft pendientes de contabilizar antes de cerrar el período.");
        }

        DB::transaction(function () use ($period, $userId) {
            // Genera asientos de cierre de ingresos, costos y gastos
            $this->generateClosingEntries($period, $userId);

            $period->update([
                'status'    => 'closed',
                'closed_by' => $userId,
                'closed_at' => now(),
            ]);
        });
    }

    /**
     * Crea un asiento de apertura manual con los saldos iniciales.
     * Protección: solo puede existir UN asiento de apertura por período.
     *
     * @param array $accountBalances [ account_id => ['debit' => x, 'credit' => y] ]
     */
    public function createOpeningEntry(
        AccountingPeriod $period,
        array            $accountBalances,
        int              $userId
    ): JournalEntry {
        $existing = JournalEntry::where('accounting_period_id', $period->id)
            ->where('reference_type', 'opening')
            ->whereIn('status', ['draft', 'posted'])
            ->first();

        if ($existing) {
            throw new \LogicException("Ya existe un asiento de apertura para este período.");
        }

        $lines = [];
        $seq   = 1;
        foreach ($accountBalances as $accountId => $amounts) {
            $lines[] = [
                'account_id'  => (int) $accountId,
                'description' => 'Saldo de apertura',
                'debit'       => (float) ($amounts['debit'] ?? 0),
                'credit'      => (float) ($amounts['credit'] ?? 0),
                'sequence'    => $seq++,
            ];
        }

        return DB::transaction(function () use ($period, $userId, $lines) {
            $entry = $this->createEntry(
                $period->company_id,
                $period,
                $period->start_date->toDateString(),
                'opening',
                $period->id,
                $userId,
                $lines
            );
            $this->postEntry($entry, $userId);
            return $entry;
        });
    }

    // ── Helpers privados ─────────────────────────────────────────────────────

    private function createEntry(
        int              $companyId,
        AccountingPeriod $period,
        string           $date,
        string           $referenceType,
        int              $referenceId,
        int              $userId,
        array            $lines
    ): JournalEntry {
        $number = $this->generateNumber($companyId, $date);

        $entry = JournalEntry::create([
            'company_id'           => $companyId,
            'accounting_period_id' => $period->id,
            'number'               => $number,
            'date'                 => $date,
            'description'          => $this->descriptionFor($referenceType, $referenceId),
            'status'               => 'draft',
            'reference_type'       => $referenceType,
            'reference_id'         => $referenceId,
            'user_id'              => $userId,
        ]);

        foreach ($lines as $seq => $line) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $line['account_id'],
                'description'      => $line['description'] ?? null,
                'debit'            => $line['debit'] ?? 0,
                'credit'           => $line['credit'] ?? 0,
                'sequence'         => $line['sequence'] ?? ($seq + 1),
            ]);
        }

        return $entry->load('lines');
    }

    /**
     * Construye las líneas contables según el tipo de referencia.
     */
    private function buildLines(string $referenceType, int $referenceId, int $companyId): array
    {
        return match ($referenceType) {
            'purchase_invoice' => $this->linesForPurchaseInvoice(
                PurchaseInvoice::with('items.product')->where('company_id', $companyId)->findOrFail($referenceId),
                $companyId
            ),
            'sale_invoice' => $this->linesForSaleInvoice(
                SaleInvoice::with('items.product')->where('company_id', $companyId)->findOrFail($referenceId),
                $companyId
            ),
            'payment' => $this->linesForPayment(
                Payment::where('company_id', $companyId)->findOrFail($referenceId),
                $companyId
            ),
            'transfer' => $this->linesForTransfer(
                Transfer::where('company_id', $companyId)->findOrFail($referenceId),
                $companyId
            ),
            default => [],
        };
    }

    private function linesForPurchaseInvoice(PurchaseInvoice $invoice, int $companyId): array
    {
        $inventory = $this->resolveConfig($companyId, 'inventory_default');
        $cxp       = $this->resolveConfig($companyId, 'cxp_default');

        $total = (float) $invoice->total;
        if ($total <= 0) {
            return [];
        }

        return [
            ['account_id' => $inventory->id, 'description' => "Compra #{$invoice->id}", 'debit' => $total, 'credit' => 0, 'sequence' => 1],
            ['account_id' => $cxp->id,       'description' => "CxP factura #{$invoice->id}", 'debit' => 0, 'credit' => $total, 'sequence' => 2],
        ];
    }

    private function linesForSaleInvoice(SaleInvoice $invoice, int $companyId): array
    {
        $cxc     = $this->resolveConfig($companyId, 'cxc_default');
        $revenue = $this->resolveConfig($companyId, 'revenue_default');

        $total = (float) $invoice->total;
        if ($total <= 0) {
            return [];
        }

        $lines = [
            ['account_id' => $cxc->id,     'description' => "CxC factura #{$invoice->id}", 'debit' => $total, 'credit' => 0,     'sequence' => 1],
            ['account_id' => $revenue->id,  'description' => "Venta #{$invoice->id}",       'debit' => 0,      'credit' => $total, 'sequence' => 2],
        ];

        // Asiento COGS: costo de los ítems físicos
        $cogsCost = $invoice->items
            ->filter(fn ($i) => $i->product && $i->product->type !== 'service')
            ->sum(fn ($i) => (float) $i->cost_at_time * (float) $i->quantity);

        if ($cogsCost > 0) {
            $cogs      = $this->resolveConfig($companyId, 'cogs_default');
            $inventory = $this->resolveConfig($companyId, 'inventory_default');

            $lines[] = ['account_id' => $cogs->id,      'description' => "COGS venta #{$invoice->id}", 'debit' => $cogsCost, 'credit' => 0,         'sequence' => 3];
            $lines[] = ['account_id' => $inventory->id, 'description' => "Inv. venta #{$invoice->id}", 'debit' => 0,         'credit' => $cogsCost, 'sequence' => 4];
        }

        return $lines;
    }

    private function linesForPayment(Payment $payment, int $companyId): array
    {
        $cashAccount = CashAccount::findOrFail($payment->cash_account_id);
        $cashChart   = $this->resolveCashAccountChart($cashAccount, $companyId);
        $amount      = (float) $payment->amount;

        if ($payment->payable_type === 'accounts_payable') {
            // Pago a proveedor: CxP (debe) / Caja (haber)
            $cxp = $this->resolveConfig($companyId, 'cxp_default');
            return [
                ['account_id' => $cxp->id,       'description' => "Pago prov. #{$payment->id}", 'debit' => $amount, 'credit' => 0,      'sequence' => 1],
                ['account_id' => $cashChart->id,  'description' => "Pago prov. #{$payment->id}", 'debit' => 0,       'credit' => $amount, 'sequence' => 2],
            ];
        }

        // Cobro de cliente: Caja (debe) / CxC (haber)
        $cxc = $this->resolveConfig($companyId, 'cxc_default');
        return [
            ['account_id' => $cashChart->id, 'description' => "Cobro cliente #{$payment->id}", 'debit' => $amount, 'credit' => 0,      'sequence' => 1],
            ['account_id' => $cxc->id,       'description' => "Cobro cliente #{$payment->id}", 'debit' => 0,       'credit' => $amount, 'sequence' => 2],
        ];
    }

    private function linesForTransfer(Transfer $transfer, int $companyId): array
    {
        $from       = CashAccount::findOrFail($transfer->from_account_id);
        $to         = CashAccount::findOrFail($transfer->to_account_id);
        $fromChart  = $this->resolveCashAccountChart($from, $companyId);
        $toChart    = $this->resolveCashAccountChart($to, $companyId);
        $amount     = (float) $transfer->amount;

        return [
            ['account_id' => $toChart->id,   'description' => "Transf. a {$to->name} #{$transfer->id}",   'debit' => $amount, 'credit' => 0,      'sequence' => 1],
            ['account_id' => $fromChart->id,  'description' => "Transf. de {$from->name} #{$transfer->id}", 'debit' => 0,       'credit' => $amount, 'sequence' => 2],
        ];
    }

    private function generateClosingEntries(AccountingPeriod $period, int $userId): void
    {
        $retainedConfig  = AccountingAccountConfig::where('company_id', $period->company_id)
            ->where('config_key', 'net_income')
            ->first();

        if (! $retainedConfig) {
            return; // no configurado, no generar cierre
        }

        $netIncomeAccount = $retainedConfig->account_id;

        // Saldos de cuentas de ingreso (nature=credit): cerrar con Debe
        $revenueTypes = ['revenue'];
        $costTypes    = ['expense', 'cost'];

        $revenueLines = $this->buildClosingLines($period, $revenueTypes, $netIncomeAccount, 'close_revenue');
        $costLines    = $this->buildClosingLines($period, $costTypes, $netIncomeAccount, 'close_costs');

        $allLines = array_merge($revenueLines, $costLines);

        if (empty($allLines)) {
            return;
        }

        $entry = $this->createEntry(
            $period->company_id,
            $period,
            $period->end_date->toDateString(),
            'closing',
            $period->id,
            $userId,
            $allLines
        );

        $this->postEntry($entry, $userId);
    }

    private function buildClosingLines(AccountingPeriod $period, array $types, int $netIncomeAccountId, string $tag): array
    {
        $accounts = ChartOfAccount::where('company_id', $period->company_id)
            ->whereIn('type', $types)
            ->where('allows_movements', true)
            ->get();

        $lines   = [];
        $seq     = 1;
        $netDebit = 0;
        $netCredit = 0;

        foreach ($accounts as $account) {
            $sumDebit  = JournalEntryLine::whereHas('entry', fn ($q) =>
                $q->where('accounting_period_id', $period->id)->where('status', 'posted')
            )->where('account_id', $account->id)->sum('debit');

            $sumCredit = JournalEntryLine::whereHas('entry', fn ($q) =>
                $q->where('accounting_period_id', $period->id)->where('status', 'posted')
            )->where('account_id', $account->id)->sum('credit');

            $balance = round($sumDebit - $sumCredit, 4);
            if (abs($balance) < 0.001) {
                continue;
            }

            if ($balance > 0) {
                // Saldo deudor: cerrar con crédito
                $lines[] = ['account_id' => $account->id, 'description' => "Cierre {$account->name}", 'debit' => 0, 'credit' => $balance, 'sequence' => $seq++];
                $netDebit += $balance;
            } else {
                $lines[] = ['account_id' => $account->id, 'description' => "Cierre {$account->name}", 'debit' => abs($balance), 'credit' => 0, 'sequence' => $seq++];
                $netCredit += abs($balance);
            }
        }

        if (empty($lines)) {
            return [];
        }

        $net = round($netDebit - $netCredit, 4);
        if (abs($net) > 0.001) {
            if ($net > 0) {
                $lines[] = ['account_id' => $netIncomeAccountId, 'description' => 'Resultado del período', 'debit' => 0, 'credit' => $net, 'sequence' => $seq];
            } else {
                $lines[] = ['account_id' => $netIncomeAccountId, 'description' => 'Resultado del período', 'debit' => abs($net), 'credit' => 0, 'sequence' => $seq];
            }
        }

        return $lines;
    }

    private function resolveConfig(int $companyId, string $key): ChartOfAccount
    {
        $config = AccountingAccountConfig::where('company_id', $companyId)
            ->where('config_key', $key)
            ->with('account')
            ->first();

        if (! $config || ! $config->account) {
            throw new \LogicException(
                "La cuenta contable '{$key}' no está configurada para esta empresa. Configure el plan de cuentas antes de contabilizar."
            );
        }

        if (! $config->account->isMovable()) {
            throw new \LogicException(
                "La cuenta '{$config->account->code}' configurada para '{$key}' no admite movimientos o está inactiva."
            );
        }

        return $config->account;
    }

    private function resolveCashAccountChart(CashAccount $cashAccount, int $companyId): ChartOfAccount
    {
        if (! $cashAccount->accounting_account_id) {
            throw new \LogicException(
                "La cuenta de efectivo '{$cashAccount->name}' no tiene una cuenta contable asignada. Configúrela en Finanzas → Cuentas."
            );
        }

        $account = ChartOfAccount::find($cashAccount->accounting_account_id);

        if (! $account || ! $account->isMovable()) {
            throw new \LogicException(
                "La cuenta contable asignada a '{$cashAccount->name}' es inválida o está inactiva."
            );
        }

        return $account;
    }

    private function findOpenPeriod(int $companyId, string $date): ?AccountingPeriod
    {
        return AccountingPeriod::where('company_id', $companyId)
            ->where('status', 'open')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }

    private function resolveDocumentDate(string $referenceType, int $referenceId): string
    {
        return match ($referenceType) {
            'purchase_invoice' => (string) PurchaseInvoice::findOrFail($referenceId)->date,
            'sale_invoice'     => (string) SaleInvoice::findOrFail($referenceId)->date,
            'payment'          => (string) Payment::findOrFail($referenceId)->date,
            'transfer'         => (string) Transfer::findOrFail($referenceId)->date,
            default            => now()->toDateString(),
        };
    }

    private function generateNumber(int $companyId, string $date): string
    {
        $year = substr($date, 0, 4);
        $last = JournalEntry::where('company_id', $companyId)
            ->whereYear('date', $year)
            ->lockForUpdate()
            ->count();

        return "JE-{$year}-" . str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }

    private function descriptionFor(string $referenceType, int $referenceId): string
    {
        return match ($referenceType) {
            'purchase_invoice' => "Factura de compra #{$referenceId}",
            'sale_invoice'     => "Factura de venta #{$referenceId}",
            'payment'          => "Pago #{$referenceId}",
            'transfer'         => "Transferencia #{$referenceId}",
            'reversal'         => "Reversión de asiento #{$referenceId}",
            'opening'          => "Asiento de apertura",
            'closing'          => "Asiento de cierre",
            default            => ucfirst($referenceType) . " #{$referenceId}",
        };
    }

    private function fillHooks(string $referenceType, int $referenceId, JournalEntry $entry): void
    {
        match ($referenceType) {
            'sale_invoice'     => SaleInvoice::where('id', $referenceId)->update(['journal_entry_id' => $entry->id]),
            'purchase_invoice' => PurchaseInvoice::where('id', $referenceId)->update(['journal_entry_id' => $entry->id]),
            'transfer'         => Transfer::where('id', $referenceId)->update(['journal_entry_id' => $entry->id]),
            'payment'          => Payment::where('id', $referenceId)->update(['journal_entry_id' => $entry->id]),
            default            => null,
        };

        // Para movimientos de stock vinculados a este documento
        if (in_array($referenceType, ['sale_invoice', 'purchase_invoice'])) {
            StockMovement::where('reference_type', $referenceType)
                ->where('reference_id', $referenceId)
                ->whereNull('journal_entry_id')
                ->update(['journal_entry_id' => $entry->id, 'posted_at' => now()]);
        }
    }

    private function clearHooks(string $referenceType, int $referenceId, int $entryId): void
    {
        match ($referenceType) {
            'sale_invoice'     => SaleInvoice::where('id', $referenceId)->where('journal_entry_id', $entryId)->update(['journal_entry_id' => null]),
            'purchase_invoice' => PurchaseInvoice::where('id', $referenceId)->where('journal_entry_id', $entryId)->update(['journal_entry_id' => null]),
            'transfer'         => Transfer::where('id', $referenceId)->where('journal_entry_id', $entryId)->update(['journal_entry_id' => null]),
            'payment'          => Payment::where('id', $referenceId)->where('journal_entry_id', $entryId)->update(['journal_entry_id' => null]),
            default            => null,
        };

        if (in_array($referenceType, ['sale_invoice', 'purchase_invoice'])) {
            StockMovement::where('reference_type', $referenceType)
                ->where('reference_id', $referenceId)
                ->where('journal_entry_id', $entryId)
                ->update(['journal_entry_id' => null, 'posted_at' => null]);
        }
    }
}
