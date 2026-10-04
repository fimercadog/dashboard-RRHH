<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ClientNoteController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ContingencyController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmployeeDocumentController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\PermissionRequestController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SegmentController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\SickLeaveController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VacationRequestController;
use App\Http\Controllers\Api\AccountPayableController;
use App\Http\Controllers\Api\PurchaseInvoiceController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\PurchaseReceiptController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\SaleOrderController;
use App\Http\Controllers\Api\SaleInvoiceController;
use App\Http\Controllers\Api\AccountsReceivableController;
use App\Http\Controllers\Api\CashAccountController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\FinancialTransactionController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\Api\ChartOfAccountController;
use App\Http\Controllers\Api\AccountingPeriodController;
use App\Http\Controllers\Api\JournalEntryController;
use App\Http\Controllers\Api\AccountingAccountConfigController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rutas sin sesion: throttle por IP para frenar fuerza bruta / enumeracion.
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1');

// Formularios publicos del sitio de marketing (demo / contacto).
Route::post('/leads', [LeadController::class, 'store'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function (): void {
    // Sin permiso: cualquier usuario autenticado.
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Cada recurso exige el permiso Spatie correspondiente (mismo mapa que el
    // menu del frontend). `can:` responde 403 si el usuario no lo tiene.
    Route::get('/dashboard', DashboardController::class)->middleware('can:dashboard.view');
    Route::get('/reports', ReportController::class)->middleware('can:reports.view');

    // Modo contingencia: el estado lo lee cualquier usuario (para renderizar el
    // banner y el modo solo-lectura); activar/desactivar exige settings.manage.
    Route::get('/contingency/status', [ContingencyController::class, 'status']);
    Route::post('/contingency/activate', [ContingencyController::class, 'activate'])->middleware('can:settings.manage');
    Route::post('/contingency/deactivate', [ContingencyController::class, 'deactivate'])->middleware('can:settings.manage');

    Route::get('/leads', [LeadController::class, 'index'])->middleware('can:leads.view');
    Route::match(['put', 'patch'], '/leads/{lead}', [LeadController::class, 'update'])->middleware('can:leads.view');

    Route::get('/company', [CompanyController::class, 'show'])->middleware('can:settings.manage');
    Route::put('/company', [CompanyController::class, 'update'])->middleware('can:settings.manage');

    Route::apiResource('employees', EmployeeController::class)->middleware('can:employees.manage');
    Route::apiResource('departments', DepartmentController::class)->middleware('can:settings.manage');
    Route::apiResource('positions', PositionController::class)->middleware('can:settings.manage');
    Route::apiResource('attendances', AttendanceController::class)->middleware('can:attendance.manage');
    Route::apiResource('vacation-requests', VacationRequestController::class)->middleware('can:requests.approve');
    Route::apiResource('permission-requests', PermissionRequestController::class)->middleware('can:requests.approve');
    Route::apiResource('sick-leaves', SickLeaveController::class)->middleware('can:requests.approve');
    Route::apiResource('employee-documents', EmployeeDocumentController::class)->middleware('can:documents.manage');
    Route::apiResource('shifts', ShiftController::class)->middleware('can:attendance.manage');
    Route::apiResource('audit-logs', AuditLogController::class)->only(['index', 'show'])->middleware('can:audit.view');
    Route::apiResource('roles', RoleController::class)->only(['index', 'store', 'update'])->middleware('can:roles.manage');
    Route::apiResource('users', UserController::class)->only(['index', 'store', 'update'])->middleware('can:users.manage');

    // CRM — Fase A.
    Route::apiResource('contacts', ContactController::class)->middleware('can:contacts.manage');
    Route::apiResource('client-notes', ClientNoteController::class)->only(['index', 'store', 'destroy'])->middleware('can:clients.manage');
    Route::apiResource('deals', DealController::class)->middleware('can:deals.manage');
    Route::apiResource('activities', ActivityController::class)->middleware('can:activities.manage');
    Route::apiResource('segments', SegmentController::class)->middleware('can:segments.manage');
    Route::post('/segments/{segment}/clients', [SegmentController::class, 'syncClients'])->middleware('can:segments.manage');

    Route::apiResource('clients', ClientController::class)->middleware('can:clients.manage');

    // Inventario — Fase B.
    Route::apiResource('categories', CategoryController::class)->middleware('can:inventory.manage');
    Route::apiResource('brands', BrandController::class)->middleware('can:inventory.manage');
    Route::apiResource('units', UnitController::class)->middleware('can:inventory.manage');
    Route::apiResource('warehouses', WarehouseController::class)->middleware('can:inventory.manage');
    Route::apiResource('products', ProductController::class)->middleware('can:inventory.manage');
    Route::get('/stock', [StockController::class, 'index'])->middleware('can:inventory.view');
    Route::get('/stock-movements', [StockMovementController::class, 'index'])->middleware('can:inventory.view');
    Route::post('/stock-movements', [StockMovementController::class, 'store'])->middleware('can:inventory.movements');

    // Compras — Fase C.
    Route::apiResource('suppliers', SupplierController::class)->middleware('can:purchases.manage');
    Route::apiResource('purchase-orders', PurchaseOrderController::class)->middleware('can:purchases.manage');
    Route::apiResource('purchase-receipts', PurchaseReceiptController::class)->middleware('can:purchases.manage');
    Route::post('/purchase-receipts/{id}/post', [PurchaseReceiptController::class, 'post'])->middleware('can:purchases.manage');
    Route::apiResource('purchase-invoices', PurchaseInvoiceController::class)->middleware('can:purchases.manage');
    Route::post('/purchase-invoices/{id}/post', [PurchaseInvoiceController::class, 'post'])->middleware('can:purchases.manage');
    Route::apiResource('accounts-payable', AccountPayableController::class)->only(['index', 'show'])->middleware('can:purchases.view');

    // Ventas — Fase D.
    Route::apiResource('quotes', QuoteController::class)->middleware('can:sales.manage');
    Route::apiResource('sale-orders', SaleOrderController::class)->middleware('can:sales.manage');
    Route::post('/sale-orders/{id}/confirm', [SaleOrderController::class, 'confirm'])->middleware('can:sales.manage');
    Route::apiResource('sale-invoices', SaleInvoiceController::class)->middleware('can:sales.manage');
    Route::post('/sale-invoices/{id}/post', [SaleInvoiceController::class, 'post'])->middleware('can:sales.manage');
    Route::post('/sale-invoices/{id}/return', [SaleInvoiceController::class, 'createReturn'])->middleware('can:sales.manage');
    Route::apiResource('accounts-receivable', AccountsReceivableController::class)->only(['index', 'show'])->middleware('can:sales.view');

    // Finanzas — Fase E.
    Route::apiResource('cash-accounts', CashAccountController::class)->middleware('can:finance.manage');
    Route::apiResource('payments', PaymentController::class)->only(['index', 'show', 'store'])->middleware('can:finance.manage');
    Route::post('/payments/{id}/cancel', [PaymentController::class, 'cancel'])->middleware('can:finance.manage');
    Route::apiResource('transfers', TransferController::class)->only(['index', 'show', 'store'])->middleware('can:finance.manage');
    Route::post('/transfers/{id}/cancel', [TransferController::class, 'cancel'])->middleware('can:finance.manage');
    Route::apiResource('financial-transactions', FinancialTransactionController::class)->only(['index', 'show'])->middleware('can:finance.view');

    // Contabilidad — Fase F.
    // GET index registrado antes del apiResource para que accounting.view no quede sombrado por accounting.manage.
    Route::get('/chart-of-accounts', [ChartOfAccountController::class, 'index'])->middleware('can:accounting.view');
    Route::apiResource('chart-of-accounts', ChartOfAccountController::class)->except(['index'])->middleware('can:accounting.manage');

    Route::get('/accounting-periods', [AccountingPeriodController::class, 'index'])->middleware('can:accounting.view');
    Route::apiResource('accounting-periods', AccountingPeriodController::class)->only(['show', 'store'])->middleware('can:accounting.manage');
    Route::post('/accounting-periods/{id}/close', [AccountingPeriodController::class, 'close'])->middleware('can:accounting.close');
    Route::post('/accounting-periods/{id}/opening', [JournalEntryController::class, 'opening'])->middleware('can:accounting.manage');

    Route::get('/journal-entries', [JournalEntryController::class, 'index'])->middleware('can:accounting.view');
    Route::apiResource('journal-entries', JournalEntryController::class)->only(['show', 'store'])->middleware('can:accounting.manage');
    Route::post('/journal-entries/{id}/reverse', [JournalEntryController::class, 'reverse'])->middleware('can:accounting.manage');
    Route::post('/journal-entries/{id}/post', [JournalEntryController::class, 'post'])->middleware('can:accounting.post');

    Route::get('/accounting-configs', [AccountingAccountConfigController::class, 'index'])->middleware('can:accounting.view');
    Route::apiResource('accounting-configs', AccountingAccountConfigController::class)->except(['index'])->middleware('can:accounting.manage');

    // El permiso por recurso se valida dentro del controlador.
    Route::get('/exports/{resource}.{format}', ExportController::class)
        ->whereIn('resource', ['employees', 'attendances', 'vacation-requests', 'permission-requests', 'sick-leaves', 'employee-documents', 'audit-logs', 'clients', 'contacts', 'deals', 'activities', 'segments', 'products', 'stock-movements'])
        ->whereIn('format', ['csv', 'pdf']);
});
