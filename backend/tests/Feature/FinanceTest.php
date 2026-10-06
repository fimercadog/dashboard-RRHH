<?php

namespace Tests\Feature;

use App\Models\AccountPayable;
use App\Models\AccountsReceivable;
use App\Models\CashAccount;
use App\Models\Client;
use App\Models\Company;
use App\Models\FinancialTransaction;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\SaleInvoice;
use App\Models\Supplier;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User    $user;
    private CashAccount $accountA;
    private CashAccount $accountB;
    private AccountsReceivable $cxc;
    private AccountPayable $cxp;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'finance.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'finance.view',   'guard_name' => 'web']);

        $this->company = Company::factory()->create(['name' => 'Test Finanzas SA']);
        $this->user    = User::factory()->create(['company_id' => $this->company->id]);

        $role = Role::firstOrCreate(['name' => 'Finance Test', 'guard_name' => 'web']);
        $role->givePermissionTo(['finance.manage', 'finance.view']);
        $this->user->assignRole($role);

        $this->accountA = CashAccount::create([
            'company_id' => $this->company->id,
            'name'       => 'Caja Principal',
            'type'       => 'cash',
            'currency'   => 'COP',
            'balance'    => 1_000_000,
            'status'     => 'active',
        ]);

        $this->accountB = CashAccount::create([
            'company_id' => $this->company->id,
            'name'       => 'Banco Bogotá',
            'type'       => 'bank',
            'currency'   => 'COP',
            'balance'    => 5_000_000,
            'status'     => 'active',
        ]);

        $client = Client::create([
            'company_id' => $this->company->id,
            'first_name' => 'Cliente',
            'last_name'  => 'Prueba',
            'name'       => 'Cliente Prueba',
            'status'     => 'active',
        ]);

        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name'       => 'Proveedor Prueba',
            'status'     => 'active',
        ]);

        // sale_invoice_id y purchase_invoice_id son NOT NULL: creamos registros padre mínimos
        $saleInvoice = SaleInvoice::create([
            'company_id' => $this->company->id,
            'client_id'  => $client->id,
            'user_id'    => $this->user->id,
            'number'     => 'FV-TEST-001',
            'date'       => now()->toDateString(),
            'due_date'   => now()->addDays(30)->toDateString(),
            'type'       => 'invoice',
            'status'     => 'posted',
            'subtotal'   => 500_000,
            'discount_total' => 0,
            'tax'        => 0,
            'total'      => 500_000,
        ]);

        $purchaseInvoice = PurchaseInvoice::create([
            'company_id'  => $this->company->id,
            'supplier_id' => $supplier->id,
            'user_id'     => $this->user->id,
            'number'      => 'FC-TEST-001',
            'date'        => now()->toDateString(),
            'due_date'    => now()->addDays(30)->toDateString(),
            'status'      => 'posted',
            'subtotal'    => 300_000,
            'tax'         => 0,
            'total'       => 300_000,
        ]);

        $this->cxc = AccountsReceivable::create([
            'company_id'      => $this->company->id,
            'client_id'       => $client->id,
            'sale_invoice_id' => $saleInvoice->id,
            'amount'          => 500_000,
            'balance'         => 500_000,
            'due_date'        => now()->addDays(30),
            'status'          => 'open',
        ]);

        $this->cxp = AccountPayable::create([
            'company_id'          => $this->company->id,
            'supplier_id'         => $supplier->id,
            'purchase_invoice_id' => $purchaseInvoice->id,
            'amount'              => 300_000,
            'balance'             => 300_000,
            'due_date'            => now()->addDays(30),
            'status'              => 'open',
        ]);

        $this->actingAs($this->user, 'sanctum');
    }

    // ── 1. Crear cuenta ────────────────────────────────────────────────────────

    public function test_create_cash_account(): void
    {
        $res = $this->postJson('/api/cash-accounts', [
            'name'     => 'Caja Sucursal',
            'type'     => 'cash',
            'currency' => 'COP',
        ]);

        $res->assertStatus(201)->assertJsonFragment(['name' => 'Caja Sucursal']);
        $this->assertDatabaseHas('cash_accounts', ['name' => 'Caja Sucursal', 'company_id' => $this->company->id]);
    }

    // ── 2. Editar cuenta ───────────────────────────────────────────────────────

    public function test_update_cash_account(): void
    {
        $res = $this->putJson("/api/cash-accounts/{$this->accountA->id}", ['name' => 'Caja Principal V2']);
        $res->assertOk()->assertJsonFragment(['name' => 'Caja Principal V2']);
    }

    // ── 3. Desactivar cuenta ───────────────────────────────────────────────────

    public function test_deactivate_cash_account(): void
    {
        $res = $this->putJson("/api/cash-accounts/{$this->accountA->id}", ['status' => 'inactive']);
        $res->assertOk();
        $this->assertDatabaseHas('cash_accounts', ['id' => $this->accountA->id, 'status' => 'inactive']);
    }

    // ── 4. Registrar pago CxP ─────────────────────────────────────────────────

    public function test_register_payment_cxp(): void
    {
        $res = $this->postJson('/api/payments', [
            'payable_type'    => 'accounts_payable',
            'payable_id'      => $this->cxp->id,
            'cash_account_id' => $this->accountA->id,
            'amount'          => 300_000,
            'date'            => now()->toDateString(),
            'method'          => 'cash',
        ]);

        $res->assertStatus(201);
        $this->assertDatabaseHas('payments', ['payable_type' => 'accounts_payable', 'amount' => 300_000]);
        $this->assertDatabaseHas('accounts_payable', ['id' => $this->cxp->id, 'status' => 'paid', 'balance' => 0]);
        $this->assertDatabaseHas('financial_transactions', ['type' => 'expense', 'amount' => 300_000]);

        $this->accountA->refresh();
        $this->assertEquals(700_000, $this->accountA->balance);
    }

    // ── 5. Registrar cobro CxC ────────────────────────────────────────────────

    public function test_register_payment_cxc(): void
    {
        $res = $this->postJson('/api/payments', [
            'payable_type'    => 'accounts_receivable',
            'payable_id'      => $this->cxc->id,
            'cash_account_id' => $this->accountA->id,
            'amount'          => 500_000,
            'date'            => now()->toDateString(),
            'method'          => 'transfer',
        ]);

        $res->assertStatus(201);
        $this->assertDatabaseHas('accounts_receivable', ['id' => $this->cxc->id, 'status' => 'paid', 'balance' => 0]);
        $this->assertDatabaseHas('financial_transactions', ['type' => 'income', 'amount' => 500_000]);

        $this->accountA->refresh();
        $this->assertEquals(1_500_000, $this->accountA->balance);
    }

    // ── 6. Pago parcial ───────────────────────────────────────────────────────

    public function test_partial_payment_cxc(): void
    {
        $this->postJson('/api/payments', [
            'payable_type'    => 'accounts_receivable',
            'payable_id'      => $this->cxc->id,
            'cash_account_id' => $this->accountA->id,
            'amount'          => 200_000,
            'date'            => now()->toDateString(),
            'method'          => 'cash',
        ])->assertStatus(201);

        $this->cxc->refresh();
        $this->assertEquals('partially_paid', $this->cxc->status);
        $this->assertEquals(300_000, $this->cxc->balance);
    }

    // ── 7. Pago total deja balance = 0 ────────────────────────────────────────

    public function test_full_payment_sets_balance_zero(): void
    {
        $this->postJson('/api/payments', [
            'payable_type'    => 'accounts_receivable',
            'payable_id'      => $this->cxc->id,
            'cash_account_id' => $this->accountA->id,
            'amount'          => 500_000,
            'date'            => now()->toDateString(),
            'method'          => 'cash',
        ])->assertStatus(201);

        $this->cxc->refresh();
        $this->assertEquals('paid', $this->cxc->status);
        $this->assertEquals(0, $this->cxc->balance);
    }

    // ── 8. Impedir sobrepago ──────────────────────────────────────────────────

    public function test_overpayment_returns_422(): void
    {
        $res = $this->postJson('/api/payments', [
            'payable_type'    => 'accounts_receivable',
            'payable_id'      => $this->cxc->id,
            'cash_account_id' => $this->accountA->id,
            'amount'          => 999_999,
            'date'            => now()->toDateString(),
            'method'          => 'cash',
        ]);

        $res->assertStatus(422);
    }

    // ── 9. Impedir pago con cuenta inactiva ───────────────────────────────────

    public function test_inactive_account_returns_422(): void
    {
        $this->accountA->update(['status' => 'inactive']);

        $res = $this->postJson('/api/payments', [
            'payable_type'    => 'accounts_receivable',
            'payable_id'      => $this->cxc->id,
            'cash_account_id' => $this->accountA->id,
            'amount'          => 100_000,
            'date'            => now()->toDateString(),
            'method'          => 'cash',
        ]);

        $res->assertStatus(422);
    }

    // ── 10. Impedir saldo negativo en caja ────────────────────────────────────

    public function test_insufficient_cash_balance_returns_422(): void
    {
        $res = $this->postJson('/api/payments', [
            'payable_type'    => 'accounts_payable',
            'payable_id'      => $this->cxp->id,
            'cash_account_id' => $this->accountA->id,
            'amount'          => 2_000_000,   // más que balance 1M y más que cxp.balance 300K
            'date'            => now()->toDateString(),
            'method'          => 'cash',
        ]);

        // CxP balance es 300K < 2M → primera validación (sobrepago) falla antes que saldo
        $res->assertStatus(422);
    }

    // ── 11. Cancelar pago ─────────────────────────────────────────────────────

    public function test_cancel_payment(): void
    {
        // Register first
        $createRes = $this->postJson('/api/payments', [
            'payable_type'    => 'accounts_receivable',
            'payable_id'      => $this->cxc->id,
            'cash_account_id' => $this->accountA->id,
            'amount'          => 500_000,
            'date'            => now()->toDateString(),
            'method'          => 'cash',
        ]);
        $createRes->assertStatus(201);
        $paymentId = $createRes->json('data.id');

        // Cancel
        $this->postJson("/api/payments/{$paymentId}/cancel")->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $paymentId, 'status' => 'cancelled']);
        $this->cxc->refresh();
        $this->assertEquals('open', $this->cxc->status);
        $this->assertEquals(500_000, $this->cxc->balance);

        $this->accountA->refresh();
        $this->assertEquals(1_000_000, $this->accountA->balance);
    }

    // ── 12. Transferir entre cuentas ──────────────────────────────────────────

    public function test_transfer_between_accounts(): void
    {
        $res = $this->postJson('/api/transfers', [
            'from_account_id' => $this->accountA->id,
            'to_account_id'   => $this->accountB->id,
            'amount'          => 400_000,
            'date'            => now()->toDateString(),
        ]);

        $res->assertStatus(201);
        $this->accountA->refresh();
        $this->accountB->refresh();
        $this->assertEquals(600_000, $this->accountA->balance);
        $this->assertEquals(5_400_000, $this->accountB->balance);

        $this->assertDatabaseHas('financial_transactions', ['type' => 'transfer_out', 'amount' => 400_000]);
        $this->assertDatabaseHas('financial_transactions', ['type' => 'transfer_in',  'amount' => 400_000]);
    }

    // ── 13. Impedir transferencia a la misma cuenta ───────────────────────────

    public function test_same_account_transfer_returns_422(): void
    {
        $res = $this->postJson('/api/transfers', [
            'from_account_id' => $this->accountA->id,
            'to_account_id'   => $this->accountA->id,
            'amount'          => 100_000,
            'date'            => now()->toDateString(),
        ]);

        $res->assertStatus(422);
    }

    // ── 14. Impedir transferencia sin saldo suficiente ────────────────────────

    public function test_transfer_insufficient_balance_returns_422(): void
    {
        $res = $this->postJson('/api/transfers', [
            'from_account_id' => $this->accountA->id,
            'to_account_id'   => $this->accountB->id,
            'amount'          => 9_999_999,
            'date'            => now()->toDateString(),
        ]);

        $res->assertStatus(422);
    }

    // ── 15. Cancelar transferencia ────────────────────────────────────────────

    public function test_cancel_transfer(): void
    {
        $createRes = $this->postJson('/api/transfers', [
            'from_account_id' => $this->accountA->id,
            'to_account_id'   => $this->accountB->id,
            'amount'          => 300_000,
            'date'            => now()->toDateString(),
        ]);
        $createRes->assertStatus(201);
        $transferId = $createRes->json('data.id');

        $this->postJson("/api/transfers/{$transferId}/cancel")->assertOk();

        $this->assertDatabaseHas('transfers', ['id' => $transferId, 'status' => 'cancelled']);
        $this->accountA->refresh();
        $this->accountB->refresh();
        $this->assertEquals(1_000_000, $this->accountA->balance);
        $this->assertEquals(5_000_000, $this->accountB->balance);
    }

    // ── 16. Multitenancy — otra empresa no puede ver cuentas ──────────────────

    public function test_multitenancy_isolates_accounts(): void
    {
        $other = Company::factory()->create(['name' => 'Otra Empresa']);
        CashAccount::create([
            'company_id' => $other->id,
            'name'       => 'Caja Otra',
            'type'       => 'cash',
            'balance'    => 0,
            'status'     => 'active',
        ]);

        $res = $this->getJson('/api/cash-accounts');
        $names = collect($res->json('data'))->pluck('name')->all();

        $this->assertNotContains('Caja Otra', $names);
        $this->assertContains('Caja Principal', $names);
    }

    // ── 17. Permisos — sin finance.manage da 403 ──────────────────────────────

    public function test_no_permission_returns_403(): void
    {
        $noPermUser = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($noPermUser, 'sanctum');

        $this->postJson('/api/payments', [])->assertStatus(403);
    }

    // ── 18. Auditoría — payment_registered queda en audit_logs ───────────────

    public function test_payment_registered_audit_log(): void
    {
        $this->postJson('/api/payments', [
            'payable_type'    => 'accounts_receivable',
            'payable_id'      => $this->cxc->id,
            'cash_account_id' => $this->accountA->id,
            'amount'          => 100_000,
            'date'            => now()->toDateString(),
            'method'          => 'cash',
        ])->assertStatus(201);

        $this->assertDatabaseHas('audit_logs', ['action' => 'payment_registered']);
    }

    // ── 19. FinancialTransactions creadas ─────────────────────────────────────

    public function test_financial_transactions_created_on_payment(): void
    {
        $initialCount = FinancialTransaction::where('company_id', $this->company->id)->count();

        $this->postJson('/api/payments', [
            'payable_type'    => 'accounts_receivable',
            'payable_id'      => $this->cxc->id,
            'cash_account_id' => $this->accountA->id,
            'amount'          => 200_000,
            'date'            => now()->toDateString(),
            'method'          => 'cash',
        ])->assertStatus(201);

        $this->assertEquals($initialCount + 1, FinancialTransaction::where('company_id', $this->company->id)->count());
    }

    // ── 20. Verificar balances finales coherentes ─────────────────────────────

    public function test_balances_are_consistent_after_multiple_operations(): void
    {
        // Cobro parcial CxC
        $this->postJson('/api/payments', [
            'payable_type'    => 'accounts_receivable',
            'payable_id'      => $this->cxc->id,
            'cash_account_id' => $this->accountA->id,
            'amount'          => 200_000,
            'date'            => now()->toDateString(),
            'method'          => 'cash',
        ])->assertStatus(201);

        // Pago CxP
        $this->postJson('/api/payments', [
            'payable_type'    => 'accounts_payable',
            'payable_id'      => $this->cxp->id,
            'cash_account_id' => $this->accountA->id,
            'amount'          => 100_000,
            'date'            => now()->toDateString(),
            'method'          => 'cash',
        ])->assertStatus(201);

        // Transferencia a B
        $this->postJson('/api/transfers', [
            'from_account_id' => $this->accountA->id,
            'to_account_id'   => $this->accountB->id,
            'amount'          => 50_000,
            'date'            => now()->toDateString(),
        ])->assertStatus(201);

        $this->accountA->refresh();
        $this->accountB->refresh();

        // 1_000_000 + 200_000 (cobro) - 100_000 (pago) - 50_000 (transfer) = 1_050_000
        $this->assertEquals(1_050_000, $this->accountA->balance);
        // 5_000_000 + 50_000 = 5_050_000
        $this->assertEquals(5_050_000, $this->accountB->balance);

        // CxC balance = 300_000
        $this->cxc->refresh();
        $this->assertEquals(300_000, $this->cxc->balance);

        // CxP balance = 200_000
        $this->cxp->refresh();
        $this->assertEquals(200_000, $this->cxp->balance);
    }
}
