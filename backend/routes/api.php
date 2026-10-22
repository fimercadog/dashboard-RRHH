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
use App\Http\Controllers\Api\PendingController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\JobVacancyController;
use App\Http\Controllers\Api\JobCandidateController;
use App\Http\Controllers\Api\JobApplicationController;
use App\Http\Controllers\Api\JobInterviewController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\WhatsappSettingController;
use App\Http\Controllers\Api\WhatsappWebhookController;
use App\Http\Controllers\Api\AiSettingController;
use App\Http\Controllers\Api\DevMockController;
use App\Http\Controllers\Api\IntegrationStatusController;
use App\Http\Controllers\Api\GoogleSheetsSettingController;
use App\Http\Controllers\Api\ZoomSettingController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\EmployeeWorkHistoryController;
use App\Http\Controllers\Api\SstIncidentController;
use App\Http\Controllers\Api\PerformanceReviewController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rutas sin sesion: throttle por IP para frenar fuerza bruta / enumeracion.
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1');

// Formularios publicos del sitio de marketing (demo / contacto).
Route::post('/leads', [LeadController::class, 'store'])->middleware('throttle:5,1');

// Webhook WhatsApp/Meta — ruta pública por diseño (verificación + recepción de mensajes).
// El token actúa como secreto por empresa; fuera de auth:sanctum.
Route::get('/webhooks/wa/{token}', [WhatsappWebhookController::class, 'verify'])->middleware('throttle:30,1');
Route::post('/webhooks/wa/{token}', [WhatsappWebhookController::class, 'receive'])->middleware('throttle:120,1');

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

    Route::get('/leads/stats', [LeadController::class, 'stats'])->middleware('can:leads.view');
    Route::get('/leads', [LeadController::class, 'index'])->middleware('can:leads.view');
    Route::match(['put', 'patch'], '/leads/{lead}', [LeadController::class, 'update'])->middleware('can:leads.view');

    Route::get('/company', [CompanyController::class, 'show'])->middleware('can:settings.manage');
    Route::put('/company', [CompanyController::class, 'update'])->middleware('can:settings.manage');

    Route::get('/employees/selector', [EmployeeController::class, 'selector']);
    Route::get('/employees/stats', [EmployeeController::class, 'stats'])->middleware('can:employees.manage');
    Route::apiResource('employees', EmployeeController::class)->except(['destroy'])->middleware('can:employees.manage');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->middleware('can:employees.delete');
    Route::post('employees/{employee}/avatar', [EmployeeController::class, 'uploadAvatar'])->middleware('can:employees.manage');
    Route::delete('employees/{employee}/avatar', [EmployeeController::class, 'deleteAvatar'])->middleware('can:employees.manage');
    Route::get('employees/{employee}/work-history', [EmployeeWorkHistoryController::class, 'index'])->middleware('can:employees.manage');
    Route::post('employees/{employee}/work-history', [EmployeeWorkHistoryController::class, 'store'])->middleware('can:employees.manage');
    Route::put('employees/{employee}/work-history/{id}', [EmployeeWorkHistoryController::class, 'update'])->middleware('can:employees.manage');
    Route::delete('employees/{employee}/work-history/{id}', [EmployeeWorkHistoryController::class, 'destroy'])->middleware('can:employees.manage');
    Route::apiResource('departments', DepartmentController::class)->middleware('can:settings.manage');
    Route::apiResource('positions', PositionController::class)->middleware('can:settings.manage');
    Route::apiResource('attendances', AttendanceController::class)->except(['destroy'])->middleware('can:attendance.manage');
    Route::delete('/attendances/{attendance}', [AttendanceController::class, 'destroy'])->middleware('can:attendance.delete');
    Route::get('attendance-stats', \App\Http\Controllers\Api\AttendanceStatsController::class)->middleware('can:attendance.manage');
    Route::get('/vacation-requests/stats', [VacationRequestController::class, 'stats'])->middleware('can:requests.approve');
    Route::apiResource('vacation-requests', VacationRequestController::class)->except(['destroy'])->middleware('can:requests.approve');
    Route::delete('/vacation-requests/{vacation_request}', [VacationRequestController::class, 'destroy'])->middleware('can:requests.delete');
    Route::get('/permission-requests/stats', [PermissionRequestController::class, 'stats'])->middleware('can:requests.approve');
    Route::apiResource('permission-requests', PermissionRequestController::class)->except(['destroy'])->middleware('can:requests.approve');
    Route::delete('/permission-requests/{permission_request}', [PermissionRequestController::class, 'destroy'])->middleware('can:requests.delete');
    Route::get('/sick-leaves/stats', [SickLeaveController::class, 'stats'])->middleware('can:requests.approve');
    Route::apiResource('sick-leaves', SickLeaveController::class)->except(['destroy'])->middleware('can:requests.approve');
    Route::delete('/sick-leaves/{sick_leave}', [SickLeaveController::class, 'destroy'])->middleware('can:requests.delete');
    Route::get('/employee-documents/stats', [EmployeeDocumentController::class, 'stats'])->middleware('can:documents.manage');
    Route::apiResource('employee-documents', EmployeeDocumentController::class)->except(['destroy'])->middleware('can:documents.manage');
    Route::delete('/employee-documents/{employee_document}', [EmployeeDocumentController::class, 'destroy'])->middleware('can:documents.delete');
    Route::get('/shifts/stats', [ShiftController::class, 'stats'])->middleware('can:attendance.manage');
    Route::apiResource('shifts', ShiftController::class)->except(['destroy'])->middleware('can:attendance.manage');
    Route::delete('/shifts/{shift}', [ShiftController::class, 'destroy'])->middleware('can:attendance.delete');
    Route::get('/audit-logs/stats', [AuditLogController::class, 'stats'])->middleware('can:audit.view');
    Route::apiResource('audit-logs', AuditLogController::class)->only(['index', 'show'])->middleware('can:audit.view');
    Route::get('/roles/stats', [RoleController::class, 'stats'])->middleware('can:roles.manage');
    Route::apiResource('roles', RoleController::class)->only(['index', 'store', 'update'])->middleware('can:roles.manage');
    Route::get('/users/stats', [UserController::class, 'stats'])->middleware('can:users.manage');
    Route::apiResource('users', UserController::class)->only(['index', 'store', 'update'])->middleware('can:users.manage');
    Route::post('users/{user}/avatar', [UserController::class, 'uploadAvatar'])->middleware('can:users.manage');
    Route::delete('users/{user}/avatar', [UserController::class, 'deleteAvatar'])->middleware('can:users.manage');

    // CRM — Fase A.
    Route::get('/contacts/stats', [ContactController::class, 'stats'])->middleware('can:contacts.manage');
    Route::apiResource('contacts', ContactController::class)->middleware('can:contacts.manage');
    Route::apiResource('client-notes', ClientNoteController::class)->only(['index', 'store', 'destroy'])->middleware('can:clients.manage');
    Route::get('/deals/stats', [DealController::class, 'stats'])->middleware('can:deals.manage');
    Route::apiResource('deals', DealController::class)->middleware('can:deals.manage');
    Route::get('/activities/stats', [ActivityController::class, 'stats'])->middleware('can:activities.manage');
    Route::apiResource('activities', ActivityController::class)->middleware('can:activities.manage');
    Route::get('/segments/stats', [SegmentController::class, 'stats'])->middleware('can:segments.manage');
    Route::apiResource('segments', SegmentController::class)->middleware('can:segments.manage');
    Route::post('/segments/{segment}/clients', [SegmentController::class, 'syncClients'])->middleware('can:segments.manage');

    Route::get('/clients/stats', [ClientController::class, 'stats'])->middleware('can:clients.manage');
    Route::apiResource('clients', ClientController::class)->middleware('can:clients.manage');

    // Inventario — Fase B.
    Route::get('/categories/stats', [CategoryController::class, 'stats'])->middleware('can:inventory.manage');
    Route::apiResource('categories', CategoryController::class)->middleware('can:inventory.manage');
    Route::get('/brands/stats', [BrandController::class, 'stats'])->middleware('can:inventory.manage');
    Route::apiResource('brands', BrandController::class)->middleware('can:inventory.manage');
    Route::get('/units/stats', [UnitController::class, 'stats'])->middleware('can:inventory.manage');
    Route::apiResource('units', UnitController::class)->middleware('can:inventory.manage');
    Route::get('/warehouses/stats', [WarehouseController::class, 'stats'])->middleware('can:inventory.manage');
    Route::apiResource('warehouses', WarehouseController::class)->middleware('can:inventory.manage');
    Route::get('/products/stats', [ProductController::class, 'stats'])->middleware('can:inventory.manage');
    Route::apiResource('products', ProductController::class)->middleware('can:inventory.manage');
    Route::get('/stock/stats', [StockController::class, 'stats'])->middleware('can:inventory.view');
    Route::get('/stock', [StockController::class, 'index'])->middleware('can:inventory.view');
    Route::get('/stock-movements/stats', [StockMovementController::class, 'stats'])->middleware('can:inventory.view');
    Route::get('/stock-movements', [StockMovementController::class, 'index'])->middleware('can:inventory.view');
    Route::post('/stock-movements', [StockMovementController::class, 'store'])->middleware('can:inventory.movements');

    // Compras — Fase C.
    Route::get('/suppliers/stats', [SupplierController::class, 'stats'])->middleware('can:purchases.view');
    Route::apiResource('suppliers', SupplierController::class)->middleware('can:purchases.manage');
    Route::get('/purchase-orders/stats', [PurchaseOrderController::class, 'stats'])->middleware('can:purchases.view');
    Route::apiResource('purchase-orders', PurchaseOrderController::class)->middleware('can:purchases.manage');
    Route::get('/purchase-receipts/stats', [PurchaseReceiptController::class, 'stats'])->middleware('can:purchases.manage');
    Route::apiResource('purchase-receipts', PurchaseReceiptController::class)->middleware('can:purchases.manage');
    Route::post('/purchase-receipts/{id}/post', [PurchaseReceiptController::class, 'post'])->middleware('can:purchases.manage');
    Route::get('/purchase-invoices/stats', [PurchaseInvoiceController::class, 'stats'])->middleware('can:purchases.view');
    Route::apiResource('purchase-invoices', PurchaseInvoiceController::class)->middleware('can:purchases.manage');
    Route::post('/purchase-invoices/{id}/post', [PurchaseInvoiceController::class, 'post'])->middleware('can:purchases.manage');
    Route::get('/accounts-payable/stats', [AccountPayableController::class, 'stats'])->middleware('can:purchases.view');
    Route::apiResource('accounts-payable', AccountPayableController::class)->only(['index', 'show'])->middleware('can:purchases.view');

    // Ventas — Fase D.
    Route::get('/quotes/stats', [QuoteController::class, 'stats'])->middleware('can:sales.view');
    Route::apiResource('quotes', QuoteController::class)->middleware('can:sales.manage');
    Route::get('/sale-orders/stats', [SaleOrderController::class, 'stats'])->middleware('can:sales.view');
    Route::apiResource('sale-orders', SaleOrderController::class)->middleware('can:sales.manage');
    Route::post('/sale-orders/{id}/confirm', [SaleOrderController::class, 'confirm'])->middleware('can:sales.manage');
    Route::get('/sale-invoices/stats', [SaleInvoiceController::class, 'stats'])->middleware('can:sales.view');
    Route::apiResource('sale-invoices', SaleInvoiceController::class)->middleware('can:sales.manage');
    Route::post('/sale-invoices/{id}/post', [SaleInvoiceController::class, 'post'])->middleware('can:sales.manage');
    Route::post('/sale-invoices/{id}/return', [SaleInvoiceController::class, 'createReturn'])->middleware('can:sales.manage');
    Route::get('/accounts-receivable/stats', [AccountsReceivableController::class, 'stats'])->middleware('can:sales.view');
    Route::apiResource('accounts-receivable', AccountsReceivableController::class)->only(['index', 'show'])->middleware('can:sales.view');

    // Finanzas — Fase E.
    Route::get('/cash-accounts/stats', [CashAccountController::class, 'stats'])->middleware('can:finance.manage');
    Route::apiResource('cash-accounts', CashAccountController::class)->middleware('can:finance.manage');
    Route::get('/payments/stats', [PaymentController::class, 'stats'])->middleware('can:finance.manage');
    Route::apiResource('payments', PaymentController::class)->only(['index', 'show', 'store'])->middleware('can:finance.manage');
    Route::post('/payments/{id}/cancel', [PaymentController::class, 'cancel'])->middleware('can:finance.manage');
    Route::get('/transfers/stats', [TransferController::class, 'stats'])->middleware('can:finance.manage');
    Route::apiResource('transfers', TransferController::class)->only(['index', 'show', 'store'])->middleware('can:finance.manage');
    Route::post('/transfers/{id}/cancel', [TransferController::class, 'cancel'])->middleware('can:finance.manage');
    Route::get('/financial-transactions/stats', [FinancialTransactionController::class, 'stats'])->middleware('can:finance.view');
    Route::apiResource('financial-transactions', FinancialTransactionController::class)->only(['index', 'show'])->middleware('can:finance.view');

    // Contabilidad — Fase F.
    // GET index registrado antes del apiResource para que accounting.view no quede sombrado por accounting.manage.
    Route::get('/chart-of-accounts/stats', [ChartOfAccountController::class, 'stats'])->middleware('can:accounting.view');
    Route::get('/chart-of-accounts', [ChartOfAccountController::class, 'index'])->middleware('can:accounting.view');
    Route::apiResource('chart-of-accounts', ChartOfAccountController::class)->except(['index'])->middleware('can:accounting.manage');

    Route::get('/accounting-periods/stats', [AccountingPeriodController::class, 'stats'])->middleware('can:accounting.view');
    Route::get('/accounting-periods', [AccountingPeriodController::class, 'index'])->middleware('can:accounting.view');
    Route::apiResource('accounting-periods', AccountingPeriodController::class)->only(['show', 'store'])->middleware('can:accounting.manage');
    Route::post('/accounting-periods/{id}/close', [AccountingPeriodController::class, 'close'])->middleware('can:accounting.close');
    Route::post('/accounting-periods/{id}/opening', [JournalEntryController::class, 'opening'])->middleware('can:accounting.manage');

    Route::get('/journal-entries/stats', [JournalEntryController::class, 'stats'])->middleware('can:accounting.view');
    Route::get('/journal-entries', [JournalEntryController::class, 'index'])->middleware('can:accounting.view');
    Route::apiResource('journal-entries', JournalEntryController::class)->only(['show', 'store'])->middleware('can:accounting.manage');
    Route::post('/journal-entries/{id}/reverse', [JournalEntryController::class, 'reverse'])->middleware('can:accounting.manage');
    Route::post('/journal-entries/{id}/post', [JournalEntryController::class, 'post'])->middleware('can:accounting.post');

    Route::get('/accounting-configs', [AccountingAccountConfigController::class, 'index'])->middleware('can:accounting.view');
    Route::apiResource('accounting-configs', AccountingAccountConfigController::class)->except(['index'])->middleware('can:accounting.manage');

    // Reclutamiento — Fase G.
    Route::get('/recruitment/stats', [JobVacancyController::class, 'stats'])->middleware('can:recruitment.view');
    Route::apiResource('job-vacancies', JobVacancyController::class)->except(['destroy'])->middleware('can:recruitment.view');
    Route::delete('/job-vacancies/{job_vacancy}', [JobVacancyController::class, 'destroy'])->middleware('can:recruitment.delete');
    Route::get('/job-candidates/stats', [JobCandidateController::class, 'stats'])->middleware('can:recruitment.view');
    Route::apiResource('job-candidates', JobCandidateController::class)->except(['destroy'])->middleware('can:recruitment.view');
    Route::delete('/job-candidates/{job_candidate}', [JobCandidateController::class, 'destroy'])->middleware('can:recruitment.delete');
    Route::get('/job-applications/stats', [JobApplicationController::class, 'stats'])->middleware('can:recruitment.view');
    Route::apiResource('job-applications', JobApplicationController::class)->except(['destroy'])->middleware('can:recruitment.view');
    Route::delete('/job-applications/{job_application}', [JobApplicationController::class, 'destroy'])->middleware('can:recruitment.delete');
    Route::post('/job-applications/{id}/hire', [JobApplicationController::class, 'hire'])->middleware('can:recruitment.update');
    Route::get('/job-interviews/stats', [JobInterviewController::class, 'stats'])->middleware('can:recruitment.view');
    Route::apiResource('job-interviews', JobInterviewController::class)->except(['destroy'])->middleware('can:recruitment.view');
    Route::delete('/job-interviews/{job_interview}', [JobInterviewController::class, 'destroy'])->middleware('can:recruitment.delete');

        // CORE — Pendientes (cualquier usuario autenticado ve los que tiene permiso).
    Route::get('/pending', PendingController::class);

    // WhatsApp — Conversaciones (Paso 4).
    Route::get('/conversations', [ConversationController::class, 'index'])->middleware('can:conversations.view');
    Route::get('/conversations/{id}', [ConversationController::class, 'show'])->middleware('can:conversations.view');
    Route::get('/conversations/{id}/messages', [ConversationController::class, 'messages'])->middleware('can:conversations.view');
    Route::post('/conversations/{id}/assign', [ConversationController::class, 'assign'])->middleware('can:conversations.manage');
    Route::post('/conversations/{id}/transfer', [ConversationController::class, 'transfer'])->middleware('can:conversations.manage');
    Route::post('/conversations/{id}/close', [ConversationController::class, 'close'])->middleware('can:conversations.close');
    Route::post('/conversations/{id}/reopen', [ConversationController::class, 'reopen'])->middleware('can:conversations.manage');
    Route::post('/conversations/{id}/messages', [ConversationController::class, 'sendMessage'])->middleware('can:conversations.manage');

    // Integraciones — Fase 0 (WhatsApp + IA).
    Route::get('/settings/whatsapp', [WhatsappSettingController::class, 'show'])->middleware('can:whatsapp.manage');
    Route::put('/settings/whatsapp', [WhatsappSettingController::class, 'update'])->middleware('can:whatsapp.manage');
    Route::post('/settings/whatsapp/generate-token', [WhatsappSettingController::class, 'generateToken'])->middleware('can:whatsapp.manage');
    Route::post('/settings/whatsapp/test', [WhatsappSettingController::class, 'test'])->middleware('can:whatsapp.manage');

    Route::get('/settings/ai', [AiSettingController::class, 'show'])->middleware('can:ai.manage');
    Route::put('/settings/ai', [AiSettingController::class, 'update'])->middleware('can:ai.manage');
    Route::post('/settings/ai/test', [AiSettingController::class, 'test'])->middleware('can:ai.manage');
    Route::get('/settings/ai/models', [AiSettingController::class, 'models'])->middleware('can:ai.manage');

    Route::get('/settings/google-sheets', [GoogleSheetsSettingController::class, 'show'])->middleware('can:google_sheets.manage');
    Route::put('/settings/google-sheets', [GoogleSheetsSettingController::class, 'update'])->middleware('can:google_sheets.manage');
    Route::post('/settings/google-sheets/test', [GoogleSheetsSettingController::class, 'test'])->middleware('can:google_sheets.manage');

    Route::get('/settings/zoom', [ZoomSettingController::class, 'show'])->middleware('can:zoom.manage');
    Route::put('/settings/zoom', [ZoomSettingController::class, 'update'])->middleware('can:zoom.manage');
    Route::post('/settings/zoom/test', [ZoomSettingController::class, 'test'])->middleware('can:zoom.manage');

    // Estado de integraciones — solo booleanos, sin credenciales.
    Route::get('/settings/integration-status', IntegrationStatusController::class)
        ->middleware('can:integrations.view');

    // CORE — Comunicaciones/Campañas.
    Route::get('/campaigns/sources', [CampaignController::class, 'sources'])->middleware('can:communications.view');
    Route::apiResource('campaigns', CampaignController::class)->middleware('can:communications.manage');
    Route::get('/campaigns/{campaign}/preview-audience', [CampaignController::class, 'previewAudience'])->middleware('can:communications.view');

    // Nómina y Liquidaciones — SPEC-018 Fase A.
    Route::apiResource('employee-contracts', \App\Http\Controllers\Api\EmployeeContractController::class)->except(['destroy'])->middleware('can:payroll.manage');
    Route::delete('employee-contracts/{employeeContract}', [\App\Http\Controllers\Api\EmployeeContractController::class, 'destroy'])->middleware('can:payroll.delete');

    Route::apiResource('company-tax-profiles', \App\Http\Controllers\Api\CompanyTaxProfileController::class)->except(['destroy'])->middleware('can:payroll.params.manage');
    Route::delete('company-tax-profiles/{companyTaxProfile}', [\App\Http\Controllers\Api\CompanyTaxProfileController::class, 'destroy'])->middleware('can:payroll.delete');
    Route::post('company-tax-profiles/{profile}/validate', [\App\Http\Controllers\Api\CompanyTaxProfileController::class, 'validate'])->middleware('can:payroll.validate_tax_profile');

    Route::prefix('payroll')->group(function () {
        Route::get('periods/stats', [\App\Http\Controllers\Api\PayrollPeriodController::class, 'stats'])->middleware('can:payroll.view');
        Route::apiResource('periods', \App\Http\Controllers\Api\PayrollPeriodController::class)->except(['destroy'])->middleware('can:payroll.manage');
        Route::delete('periods/{period}', [\App\Http\Controllers\Api\PayrollPeriodController::class, 'destroy'])->middleware('can:payroll.delete');
        Route::post('periods/{period}/close', [\App\Http\Controllers\Api\PayrollPeriodController::class, 'close'])->middleware('can:payroll.close');
        Route::post('periods/{period}/complementary', [\App\Http\Controllers\Api\PayrollPeriodController::class, 'complementary'])->middleware('can:payroll.manage');

        Route::post('periods/{period}/run', [\App\Http\Controllers\Api\PayrollRunController::class, 'run'])->middleware('can:payroll.manage');
        Route::get('runs/{run}', [\App\Http\Controllers\Api\PayrollRunController::class, 'show'])->middleware('can:payroll.view');
        Route::post('runs/{run}/approve', [\App\Http\Controllers\Api\PayrollRunController::class, 'approve'])->middleware('can:payroll.close');

        Route::apiResource('parameters', \App\Http\Controllers\Api\PayrollParameterController::class)->except(['destroy'])->middleware('can:payroll.params.manage');
        Route::delete('parameters/{parameter}', [\App\Http\Controllers\Api\PayrollParameterController::class, 'destroy'])->middleware('can:payroll.delete');
        Route::post('parameters/{parameter}/values', [\App\Http\Controllers\Api\PayrollParameterController::class, 'addValue'])->middleware('can:payroll.params.manage');
        Route::get('parameters/{parameter}/history', [\App\Http\Controllers\Api\PayrollParameterController::class, 'history'])->middleware('can:payroll.params.manage');
    });

    // Importación masiva — SPEC-015.
    Route::post('/import/{entity}', [ImportController::class, 'upload'])
        ->whereIn('entity', ['employees', 'clients', 'products']);
    Route::get('/import/{importJob}/status', [ImportController::class, 'status']);

    // SST — Seguridad y Salud en el Trabajo.
    Route::get('sst/incidents/stats', [SstIncidentController::class, 'stats'])->middleware('can:sst.manage');
    Route::apiResource('sst/incidents', SstIncidentController::class)->except(['destroy'])->middleware('can:sst.manage');
    Route::delete('sst/incidents/{incident}', [SstIncidentController::class, 'destroy'])->middleware('can:sst.delete');
    Route::post('sst/incidents/{incident}/close', [SstIncidentController::class, 'close'])->middleware('can:sst.manage');

    // Desempeño — Evaluaciones de desempeño.
    Route::get('/performance-reviews/stats', [PerformanceReviewController::class, 'stats'])->middleware('can:desempeno.manage');
    Route::apiResource('performance-reviews', PerformanceReviewController::class)->except(['destroy'])->middleware('can:desempeno.manage');
    Route::delete('performance-reviews/{review}', [PerformanceReviewController::class, 'destroy'])->middleware('can:desempeno.delete');
    Route::post('performance-reviews/{review}/submit', [PerformanceReviewController::class, 'submit'])->middleware('can:desempeno.manage');

    // El permiso por recurso se valida dentro del controlador.
    Route::get('/exports/{resource}.{format}', ExportController::class)
        ->whereIn('resource', [
            'users', 'departments', 'positions',
            'employees', 'attendances', 'vacation-requests', 'permission-requests', 'sick-leaves', 'employee-documents', 'audit-logs', 'shifts',
            'job-vacancies', 'job-candidates', 'job-interviews', 'job-applications',
            'products', 'stock-movements', 'product-stock', 'categories', 'brands', 'warehouses', 'units',
            'clients', 'contacts', 'deals', 'activities', 'segments',
            'suppliers', 'purchase-orders', 'purchase-invoices', 'purchase-receipts', 'accounts-payable',
            'quotes', 'sale-orders', 'sale-invoices', 'accounts-receivable',
            'payments', 'transfers', 'cash-accounts', 'financial-transactions',
            'accounting-periods', 'journal-entries', 'chart-of-accounts',
        ])
        ->whereIn('format', ['csv', 'pdf']);
});

// ── Mocks de desarrollo (F40) ──────────────────────────────────────────────
// Solo registradas cuando DEV_MOCKS_ENABLED=true y fuera de producción.
// ponytail: guard doble — env check + APP_ENV para evitar activación accidental.
if (config('app.dev_mocks_enabled') && ! app()->environment('production')) {
    Route::prefix('dev/mocks')->group(function () {
        // WhatsApp mock
        Route::post('/wa/messages',   [DevMockController::class, 'waSend']);
        Route::post('/wa/inbound',    [DevMockController::class, 'waInbound']);
        Route::get('/wa/status',      [DevMockController::class, 'waStatus']);
        // AI mock (OpenAI-compatible)
        Route::post('/ai/chat/completions', [DevMockController::class, 'aiCompletions']);
        Route::get('/ai/models',            [DevMockController::class, 'aiModels']);
    });
}

