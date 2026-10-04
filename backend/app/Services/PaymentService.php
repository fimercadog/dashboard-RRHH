<?php

namespace App\Services;

use App\Models\AccountPayable;
use App\Models\AccountsReceivable;
use App\Models\CashAccount;
use App\Models\FinancialTransaction;
use App\Models\Payment;
use App\Models\Transfer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private AccountingService $accounting) {}
    /**
     * Registra un pago o cobro contra una CxP o CxC.
     * PaymentService es el ÚNICO escritor de cash_accounts.balance y financial_transactions.
     */
    public function registerPayment(
        string  $payableType,
        int     $payableId,
        int     $cashAccountId,
        float   $amount,
        string  $method,
        string  $date,
        int     $companyId,
        int     $userId,
        ?string $reference = null,
        ?string $notes = null
    ): Payment {
        $payable = $this->resolvePayable($payableType, $payableId, $companyId);
        $account = CashAccount::where('company_id', $companyId)->findOrFail($cashAccountId);

        $this->assertPayableOpen($payable);
        $this->assertAmountValid($amount, $payable->balance);

        if ($account->status !== 'active') {
            throw new \LogicException('La cuenta de efectivo está inactiva.');
        }

        $isExpense = $payableType === 'accounts_payable';

        if ($isExpense && $account->balance < $amount) {
            throw new \LogicException("Saldo insuficiente en la cuenta de efectivo (disponible: {$account->balance}).");
        }

        $payment = DB::transaction(function () use ($payable, $payableType, $payableId, $account, $amount, $method, $date, $companyId, $userId, $reference, $notes, $isExpense) {
            $newBalance = round($payable->balance - $amount, 4);
            $newStatus  = $newBalance <= 0 ? 'paid' : 'partially_paid';
            $payable->update(['balance' => $newBalance, 'status' => $newStatus]);

            $payment = Payment::create([
                'company_id'      => $companyId,
                'cash_account_id' => $account->id,
                'user_id'         => $userId,
                'payable_type'    => $payableType,
                'payable_id'      => $payableId,
                'amount'          => $amount,
                'date'            => $date,
                'method'          => $method,
                'reference'       => $reference,
                'notes'           => $notes,
                'status'          => 'active',
            ]);

            // Actualiza saldo de caja (único punto de escritura)
            $account->balance = round($isExpense
                ? $account->balance - $amount
                : $account->balance + $amount, 4);
            $account->save();

            FinancialTransaction::create([
                'company_id'      => $companyId,
                'cash_account_id' => $account->id,
                'type'            => $isExpense ? 'expense' : 'income',
                'amount'          => $amount,
                'date'            => $date,
                'reference_type'  => 'payment',
                'reference_id'    => $payment->id,
                'description'     => $isExpense
                    ? "Pago a proveedor · Pago #{$payment->id}"
                    : "Cobro a cliente · Pago #{$payment->id}",
                'user_id'         => $userId,
            ]);

            return $payment;
        });

        // Contabilizar pago (silencioso si no hay período abierto)
        $this->accounting->generateAndPost('payment', $payment->id, $payment->company_id, $userId);

        return $payment;
    }

    /**
     * Cancela un pago activo y revierte todos sus efectos financieros.
     */
    public function cancelPayment(Payment $payment, int $userId): void
    {
        if ($payment->status !== 'active') {
            throw new \LogicException('El pago ya fue cancelado.');
        }

        $payable = $this->resolvePayable($payment->payable_type, $payment->payable_id, $payment->company_id);
        $account = CashAccount::findOrFail($payment->cash_account_id);
        $isExpense = $payment->payable_type === 'accounts_payable';

        // Si es un cobro, al revertir sacamos dinero de la caja: validar saldo
        if (! $isExpense && $account->balance < $payment->amount) {
            throw new \LogicException("No se puede revertir: la cuenta de efectivo no tiene saldo suficiente ({$account->balance}).");
        }

        DB::transaction(function () use ($payment, $payable, $account, $isExpense, $userId) {
            $newBalance = round($payable->balance + $payment->amount, 4);
            $newStatus  = $newBalance >= $payable->amount ? 'open' : 'partially_paid';
            $payable->update(['balance' => $newBalance, 'status' => $newStatus]);

            $account->balance = round($isExpense
                ? $account->balance + $payment->amount
                : $account->balance - $payment->amount, 4);
            $account->save();

            // Transacción compensatoria (no se borra la original)
            FinancialTransaction::create([
                'company_id'      => $payment->company_id,
                'cash_account_id' => $account->id,
                'type'            => $isExpense ? 'income' : 'expense',
                'amount'          => $payment->amount,
                'date'            => now()->toDateString(),
                'reference_type'  => 'payment',
                'reference_id'    => $payment->id,
                'description'     => "Reverso de pago #{$payment->id}",
                'user_id'         => $userId,
            ]);

            $payment->update(['status' => 'cancelled']);
        });

        // Revertir asiento contable si existe
        if ($payment->journal_entry_id) {
            $entry = \App\Models\JournalEntry::find($payment->journal_entry_id);
            if ($entry && $entry->status === 'posted') {
                $this->accounting->reverseEntry($entry, $userId);
            }
        }
    }

    /**
     * Transfiere dinero entre dos cuentas de la misma empresa.
     */
    public function transfer(
        int     $fromAccountId,
        int     $toAccountId,
        float   $amount,
        string  $date,
        int     $companyId,
        int     $userId,
        ?string $reference = null,
        ?string $notes = null
    ): Transfer {
        if ($fromAccountId === $toAccountId) {
            throw new \LogicException('Las cuentas de origen y destino deben ser diferentes.');
        }
        if ($amount <= 0) {
            throw new \LogicException('El monto debe ser mayor a cero.');
        }

        $from = CashAccount::where('company_id', $companyId)->findOrFail($fromAccountId);
        $to   = CashAccount::where('company_id', $companyId)->findOrFail($toAccountId);

        if ($from->status !== 'active' || $to->status !== 'active') {
            throw new \LogicException('Ambas cuentas deben estar activas.');
        }
        if ($from->balance < $amount) {
            throw new \LogicException("Saldo insuficiente en la cuenta origen (disponible: {$from->balance}).");
        }

        $transfer = DB::transaction(function () use ($from, $to, $amount, $date, $companyId, $userId, $reference, $notes) {
            $from->balance = round($from->balance - $amount, 4);
            $from->save();
            $to->balance = round($to->balance + $amount, 4);
            $to->save();

            $transfer = Transfer::create([
                'company_id'      => $companyId,
                'from_account_id' => $from->id,
                'to_account_id'   => $to->id,
                'user_id'         => $userId,
                'amount'          => $amount,
                'date'            => $date,
                'reference'       => $reference,
                'notes'           => $notes,
                'status'          => 'active',
            ]);

            FinancialTransaction::create([
                'company_id'      => $companyId,
                'cash_account_id' => $from->id,
                'type'            => 'transfer_out',
                'amount'          => $amount,
                'date'            => $date,
                'reference_type'  => 'transfer',
                'reference_id'    => $transfer->id,
                'description'     => "Transferencia a {$to->name} · Transf. #{$transfer->id}",
                'user_id'         => $userId,
            ]);

            FinancialTransaction::create([
                'company_id'      => $companyId,
                'cash_account_id' => $to->id,
                'type'            => 'transfer_in',
                'amount'          => $amount,
                'date'            => $date,
                'reference_type'  => 'transfer',
                'reference_id'    => $transfer->id,
                'description'     => "Transferencia desde {$from->name} · Transf. #{$transfer->id}",
                'user_id'         => $userId,
            ]);

            return $transfer;
        });

        // Contabilizar transferencia (silencioso si no hay período abierto)
        $this->accounting->generateAndPost('transfer', $transfer->id, $transfer->company_id, $userId);

        return $transfer;
    }

    /**
     * Cancela una transferencia activa y revierte los saldos.
     * NO borra las FinancialTransactions originales; crea compensatorias.
     */
    public function cancelTransfer(Transfer $transfer, int $userId): void
    {
        if ($transfer->status !== 'active') {
            throw new \LogicException('La transferencia ya fue cancelada.');
        }

        $from = CashAccount::findOrFail($transfer->from_account_id);
        $to   = CashAccount::findOrFail($transfer->to_account_id);

        // Para revertir, el destino debe tener el monto que recibió
        if ($to->balance < $transfer->amount) {
            throw new \LogicException(
                "No se puede revertir: la cuenta destino ({$to->name}) no tiene saldo suficiente (disponible: {$to->balance})."
            );
        }

        DB::transaction(function () use ($transfer, $from, $to, $userId) {
            $from->balance = round($from->balance + $transfer->amount, 4);
            $from->save();
            $to->balance = round($to->balance - $transfer->amount, 4);
            $to->save();

            // Transacciones compensatorias (par inverso, misma transfer)
            FinancialTransaction::create([
                'company_id'      => $transfer->company_id,
                'cash_account_id' => $to->id,
                'type'            => 'transfer_out',
                'amount'          => $transfer->amount,
                'date'            => now()->toDateString(),
                'reference_type'  => 'transfer',
                'reference_id'    => $transfer->id,
                'description'     => "Reverso transferencia #{$transfer->id} (de {$to->name})",
                'user_id'         => $userId,
            ]);

            FinancialTransaction::create([
                'company_id'      => $transfer->company_id,
                'cash_account_id' => $from->id,
                'type'            => 'transfer_in',
                'amount'          => $transfer->amount,
                'date'            => now()->toDateString(),
                'reference_type'  => 'transfer',
                'reference_id'    => $transfer->id,
                'description'     => "Reverso transferencia #{$transfer->id} (a {$from->name})",
                'user_id'         => $userId,
            ]);

            $transfer->update(['status' => 'cancelled']);
        });

        // Revertir asiento contable de la transferencia si existe
        if ($transfer->journal_entry_id) {
            $entry = \App\Models\JournalEntry::find($transfer->journal_entry_id);
            if ($entry && $entry->status === 'posted') {
                $this->accounting->reverseEntry($entry, $userId);
            }
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function resolvePayable(string $type, int $id, int $companyId): Model
    {
        return match ($type) {
            'accounts_receivable' => AccountsReceivable::where('company_id', $companyId)->findOrFail($id),
            'accounts_payable'    => AccountPayable::where('company_id', $companyId)->findOrFail($id),
            default               => throw new \InvalidArgumentException("Tipo de cuenta inválido: {$type}"),
        };
    }

    private function assertPayableOpen(Model $payable): void
    {
        if (! in_array($payable->status, ['open', 'partially_paid'])) {
            throw new \LogicException("La cuenta no admite pagos en su estado actual ({$payable->status}).");
        }
    }

    private function assertAmountValid(float $amount, float $balance): void
    {
        if ($amount <= 0) {
            throw new \LogicException('El monto debe ser mayor a cero.');
        }
        if ($amount > $balance) {
            throw new \LogicException("El monto ({$amount}) excede el saldo pendiente ({$balance}).");
        }
    }
}
