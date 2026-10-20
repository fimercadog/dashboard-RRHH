<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Models\AccountPayable;
use App\Models\AccountsReceivable;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Brand;
use App\Models\CashAccount;
use App\Models\Category;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\FinancialTransaction;
use App\Models\JobCandidate;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Transfer;
use App\Models\JobInterview;
use App\Models\JobVacancy;
use App\Models\AuditLog;
use App\Models\PermissionRequest;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Models\Quote;
use App\Models\SaleInvoice;
use App\Models\SaleOrder;
use App\Models\Segment;
use App\Models\Shift;
use App\Models\SickLeave;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\VacationRequest;
use App\Models\Department;
use App\Models\JobApplication;
use App\Models\Position;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\TableQueryService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
    use ResolvesCompany;

    private const CSV_CHUNK = 500;
    private const PDF_LIMIT = 2000;

    private array $resourceNames = [
        // Organización y usuarios
        'users'            => 'usuarios',
        'departments'      => 'departamentos',
        'positions'        => 'cargos',
        // RRHH
        'employees'           => 'empleados',
        'attendances'         => 'asistencias',
        'vacation-requests'   => 'vacaciones',
        'permission-requests' => 'permisos_laborales',
        'sick-leaves'         => 'incapacidades',
        'employee-documents'  => 'documentos_empleados',
        'audit-logs'          => 'auditoria',
        'shifts'              => 'turnos',
        // Reclutamiento
        'job-vacancies'    => 'vacantes',
        'job-candidates'   => 'candidatos',
        'job-interviews'   => 'entrevistas',
        'job-applications' => 'postulaciones',
        // Inventario
        'products'        => 'productos',
        'stock-movements' => 'movimientos_inventario',
        'product-stock'   => 'stock_productos',
        'categories'      => 'categorias',
        'brands'          => 'marcas',
        'warehouses'      => 'bodegas',
        'units'           => 'unidades',
        // CRM
        'clients'    => 'clientes',
        'contacts'   => 'contactos',
        'deals'      => 'negocios',
        'activities' => 'actividades',
        'segments'   => 'segmentos',
        // Compras
        'suppliers'         => 'proveedores',
        'purchase-orders'   => 'ordenes_compra',
        'purchase-invoices' => 'facturas_compra',
        'purchase-receipts' => 'recepciones',
        'accounts-payable'  => 'cuentas_por_pagar',
        // Ventas
        'quotes'              => 'cotizaciones',
        'sale-orders'         => 'pedidos_venta',
        'sale-invoices'       => 'facturas_venta',
        'accounts-receivable' => 'cuentas_por_cobrar',
        // Finanzas
        'payments'               => 'pagos',
        'transfers'              => 'transferencias',
        'cash-accounts'          => 'cuentas_caja',
        'financial-transactions' => 'transacciones_financieras',
        // Contabilidad
        'accounting-periods' => 'periodos_contables',
        'journal-entries'    => 'asientos_contables',
        'chart-of-accounts'  => 'plan_cuentas',
    ];

    /** @return array<string, array{0:class-string, 1:string[], 2:callable|null, 3:callable|null}> */
    private function map(): array
    {
        return [
        // Organización y usuarios
        'users' => [
            User::class,
            ['Nombre', 'Correo', 'Empleado', 'Roles', 'Estado'],
            fn($q) => $q->with('employee:id,first_name,last_name,employee_code'),
            fn($row) => [
                $row->name,
                $row->email,
                $row->employee ? "{$row->employee->first_name} {$row->employee->last_name}" : '',
                $row->getRoleNames()->join(', '),
                $row->status,
            ],
        ],
        'departments' => [
            Department::class,
            ['Departamento', 'Descripcion', 'Estado'],
            null,
            fn($row) => [$row->name, $row->description ?? '', $row->status],
        ],
        'positions' => [
            Position::class,
            ['Cargo', 'Departamento', 'Descripcion', 'Estado'],
            fn($q) => $q->with('department:id,name'),
            fn($row) => [
                $row->name,
                $row->department?->name ?? '',
                $row->description ?? '',
                $row->status,
            ],
        ],

        // RRHH
        'employees'           => [Employee::class,        ['employee_code', 'first_name', 'last_name', 'email', 'employment_status']],
        'attendances'         => [Attendance::class,      ['employee_id', 'date', 'status', 'check_in', 'check_out', 'late_minutes']],
        'vacation-requests'   => [VacationRequest::class, ['employee_id', 'start_date', 'end_date', 'requested_days', 'status']],
        'permission-requests' => [PermissionRequest::class, ['employee_id', 'type', 'start_date', 'end_date', 'status']],
        'sick-leaves'         => [SickLeave::class,       ['employee_id', 'start_date', 'end_date', 'days', 'type', 'status']],
        'employee-documents'  => [EmployeeDocument::class, ['employee_id', 'document_type', 'name', 'expiration_date', 'status']],
        'audit-logs'          => [AuditLog::class,        ['user_id', 'action', 'module', 'entity', 'entity_id', 'created_at']],
        'shifts'              => [Shift::class,           ['name', 'start_time', 'end_time', 'break_minutes', 'status']],

        // Reclutamiento
        'job-vacancies'  => [
            JobVacancy::class,
            ['Título', 'Departamento', 'Vacantes', 'Estado', 'Apertura', 'Cierre'],
            fn($q) => $q->with('department:id,name'),
            fn($row) => [
                $row->title,
                $row->department?->name ?? '',
                $row->vacancies_count,
                $row->status,
                $row->opened_at?->format('Y-m-d'),
                $row->closed_at?->format('Y-m-d'),
            ],
        ],
        'job-candidates' => [JobCandidate::class, ['first_name', 'last_name', 'identification_number', 'email', 'phone', 'observations', 'created_at']],
        'job-applications' => [
            JobApplication::class,
            ['Candidato', 'Vacante', 'Fecha', 'Estado', 'Contratado'],
            fn($q) => $q->with(['candidate:id,first_name,last_name', 'vacancy:id,title']),
            fn($row) => [
                $row->candidate ? "{$row->candidate->first_name} {$row->candidate->last_name}" : '',
                $row->vacancy?->title ?? '',
                $row->applied_at?->format('Y-m-d'),
                $row->status,
                $row->hired_employee_id ? 'Sí' : 'No',
            ],
        ],
        'job-interviews' => [
            JobInterview::class,
            ['Candidato', 'Vacante', 'Entrevistador', 'Tipo', 'Resultado', 'Fecha', 'Notas'],
            fn($q) => $q->with([
                'application.candidate:id,first_name,last_name',
                'application.vacancy:id,title',
                'interviewer:id,name',
            ]),
            fn($row) => [
                $row->application?->candidate ? trim($row->application->candidate->first_name.' '.$row->application->candidate->last_name) : '',
                $row->application?->vacancy?->title ?? '',
                $row->interviewer?->name ?? '',
                $row->type,
                $row->result ?? '',
                $row->scheduled_at?->format('Y-m-d H:i'),
                $row->notes ?? '',
            ],
        ],

        // Inventario
        'products'        => [Product::class,      ['sku', 'name', 'type', 'cost_price', 'sale_price', 'min_stock', 'status']],
        'stock-movements' => [StockMovement::class, ['product_id', 'warehouse_id', 'type', 'quantity', 'unit_cost', 'total_cost', 'reference_type', 'created_at']],
        'product-stock'   => [
            ProductStock::class,
            ['SKU', 'Producto', 'Bodega', 'Cantidad', 'Costo promedio'],
            fn($q) => $q->with(['product:id,sku,name', 'warehouse:id,name']),
            fn($row) => [
                $row->product?->sku ?? '',
                $row->product?->name ?? '',
                $row->warehouse?->name ?? '',
                $row->quantity,
                $row->avg_cost,
            ],
        ],
        'categories'      => [Category::class,  ['name', 'status']],
        'brands'          => [Brand::class,     ['name', 'status']],
        'warehouses'      => [Warehouse::class, ['name', 'location', 'status']],
        'units'           => [Unit::class,      ['name', 'abbreviation', 'status']],

        // CRM
        'clients'    => [Client::class,  ['first_name', 'last_name', 'company_name', 'identification_number', 'email', 'phone', 'city', 'status']],
        'contacts'   => [Contact::class, ['client_id', 'name', 'job_title', 'email', 'phone']],
        'deals'      => [Deal::class,    ['client_id', 'owner_id', 'title', 'amount', 'stage', 'expected_close_date']],
        'activities' => [Activity::class, ['deal_id', 'client_id', 'type', 'title', 'status', 'due_at', 'done_at']],
        'segments'   => [Segment::class, ['name', 'description']],

        // Compras
        'suppliers' => [Supplier::class, ['name', 'nit', 'email', 'phone', 'address', 'payment_terms', 'status']],
        'purchase-orders' => [
            PurchaseOrder::class,
            ['Número', 'Proveedor', 'Fecha', 'Fecha esperada', 'Estado', 'Subtotal', 'IVA', 'Total'],
            fn($q) => $q->with('supplier:id,name'),
            fn($row) => [
                $row->number,
                $row->supplier?->name ?? '',
                $row->date?->format('Y-m-d'),
                $row->expected_date?->format('Y-m-d'),
                $row->status,
                $row->subtotal,
                $row->tax,
                $row->total,
            ],
        ],
        'purchase-invoices' => [
            PurchaseInvoice::class,
            ['Número', 'Proveedor', 'Fecha', 'Vencimiento', 'Estado', 'Subtotal', 'IVA', 'Total'],
            fn($q) => $q->with('supplier:id,name'),
            fn($row) => [
                $row->number,
                $row->supplier?->name ?? '',
                $row->date?->format('Y-m-d'),
                $row->due_date?->format('Y-m-d'),
                $row->status,
                $row->subtotal,
                $row->tax,
                $row->total,
            ],
        ],
        'purchase-receipts' => [
            PurchaseReceipt::class,
            ['Número', 'Proveedor', 'Bodega', 'Fecha', 'Tipo', 'Estado'],
            fn($q) => $q->with(['supplier:id,name', 'warehouse:id,name']),
            fn($row) => [
                $row->number,
                $row->supplier?->name ?? '',
                $row->warehouse?->name ?? '',
                $row->date?->format('Y-m-d'),
                $row->type,
                $row->status,
            ],
        ],
        'accounts-payable' => [
            AccountPayable::class,
            ['Proveedor', 'N° Factura', 'Monto', 'Saldo', 'Vencimiento', 'Estado'],
            fn($q) => $q->with(['supplier:id,name', 'purchaseInvoice:id,number']),
            fn($row) => [
                $row->supplier?->name ?? '',
                $row->purchaseInvoice?->number ?? '',
                $row->amount,
                $row->balance,
                $row->due_date?->format('Y-m-d'),
                $row->status,
            ],
        ],

        // Ventas
        'quotes' => [
            Quote::class,
            ['Número', 'Cliente', 'Fecha', 'Válida hasta', 'Estado', 'Subtotal', 'Descuento', 'IVA', 'Total'],
            fn($q) => $q->with('client:id,first_name,last_name,company_name'),
            fn($row) => [
                $row->number,
                $row->client ? ($row->client->company_name ?: trim($row->client->first_name.' '.$row->client->last_name)) : '',
                $row->date?->format('Y-m-d'),
                $row->valid_until?->format('Y-m-d'),
                $row->status,
                $row->subtotal,
                $row->discount_total,
                $row->tax,
                $row->total,
            ],
        ],
        'sale-orders' => [
            SaleOrder::class,
            ['Número', 'Cliente', 'Fecha', 'Estado', 'Subtotal', 'Descuento', 'IVA', 'Total'],
            fn($q) => $q->with('client:id,first_name,last_name,company_name'),
            fn($row) => [
                $row->number,
                $row->client ? ($row->client->company_name ?: trim($row->client->first_name.' '.$row->client->last_name)) : '',
                $row->date?->format('Y-m-d'),
                $row->status,
                $row->subtotal,
                $row->discount_total,
                $row->tax,
                $row->total,
            ],
        ],
        'sale-invoices' => [
            SaleInvoice::class,
            ['Número', 'Cliente', 'Fecha', 'Vencimiento', 'Tipo', 'Estado', 'Subtotal', 'Descuento', 'IVA', 'Total'],
            fn($q) => $q->with('client:id,first_name,last_name,company_name'),
            fn($row) => [
                $row->number,
                $row->client ? ($row->client->company_name ?: trim($row->client->first_name.' '.$row->client->last_name)) : '',
                $row->date?->format('Y-m-d'),
                $row->due_date?->format('Y-m-d'),
                $row->type,
                $row->status,
                $row->subtotal,
                $row->discount_total,
                $row->tax,
                $row->total,
            ],
        ],
        'accounts-receivable' => [
            AccountsReceivable::class,
            ['Cliente', 'N° Factura', 'Monto', 'Saldo', 'Vencimiento', 'Estado'],
            fn($q) => $q->with(['client:id,first_name,last_name,company_name', 'saleInvoice:id,number']),
            fn($row) => [
                $row->client ? ($row->client->company_name ?: trim($row->client->first_name.' '.$row->client->last_name)) : '',
                $row->saleInvoice?->number ?? '',
                $row->amount,
                $row->balance,
                $row->due_date?->format('Y-m-d'),
                $row->status,
            ],
        ],

        // Finanzas
        'payments' => [
            Payment::class,
            ['Tipo', 'Monto', 'Fecha', 'Método', 'Cuenta caja', 'Referencia', 'Estado'],
            fn($q) => $q->with('cashAccount:id,name'),
            fn($row) => [
                $row->payable_type,
                $row->amount,
                $row->date?->format('Y-m-d'),
                $row->method,
                $row->cashAccount?->name ?? '',
                $row->reference ?? '',
                $row->status,
            ],
        ],
        'transfers' => [
            Transfer::class,
            ['Origen', 'Destino', 'Monto', 'Fecha', 'Referencia', 'Estado'],
            fn($q) => $q->with(['fromAccount:id,name', 'toAccount:id,name']),
            fn($row) => [
                $row->fromAccount?->name ?? '',
                $row->toAccount?->name ?? '',
                $row->amount,
                $row->date?->format('Y-m-d'),
                $row->reference ?? '',
                $row->status,
            ],
        ],
        'cash-accounts' => [CashAccount::class, ['name', 'type', 'currency', 'balance', 'status']],
        'financial-transactions' => [
            FinancialTransaction::class,
            ['Cuenta', 'Tipo', 'Monto', 'Fecha', 'Descripción', 'Tipo referencia'],
            fn($q) => $q->with('cashAccount:id,name'),
            fn($row) => [
                $row->cashAccount?->name ?? '',
                $row->type,
                $row->amount,
                $row->date?->format('Y-m-d'),
                $row->description ?? '',
                $row->reference_type ?? '',
            ],
        ],

        // Contabilidad
        'accounting-periods' => [
            AccountingPeriod::class,
            ['Nombre', 'Inicio', 'Fin', 'Estado', 'Cerrado el'],
            null,
            fn($row) => [
                $row->name,
                $row->start_date?->format('Y-m-d'),
                $row->end_date?->format('Y-m-d'),
                $row->status,
                $row->closed_at?->format('Y-m-d') ?? '',
            ],
        ],
        'journal-entries' => [
            JournalEntry::class,
            ['Número', 'Fecha', 'Descripción', 'Referencia', 'Estado'],
            null,
            fn($row) => [
                $row->number,
                $row->date?->format('Y-m-d'),
                $row->description,
                $row->reference_type ? $row->reference_type.'#'.$row->reference_id : '',
                $row->status,
            ],
        ],
        'chart-of-accounts' => [
            ChartOfAccount::class,
            ['Código', 'Nombre', 'Tipo', 'Naturaleza', 'Nivel', 'Permite movimientos', 'Estado'],
            null,
            fn($row) => [
                $row->code,
                $row->name,
                $row->type,
                $row->nature,
                $row->level,
                $row->allows_movements ? 'Sí' : 'No',
                $row->status,
            ],
        ],
        ];
    }

    private array $permissionByResource = [
        // Organización y usuarios
        'users'            => 'users.manage',
        'departments'      => 'settings.manage',
        'positions'        => 'settings.manage',
        // RRHH
        'employees'           => 'employees.manage',
        'attendances'         => 'attendance.manage',
        'vacation-requests'   => 'requests.approve',
        'permission-requests' => 'requests.approve',
        'sick-leaves'         => 'requests.approve',
        'employee-documents'  => 'documents.manage',
        'audit-logs'          => 'audit.view',
        'shifts'              => 'attendance.manage',
        // Reclutamiento
        'job-vacancies'       => 'recruitment.view',
        'job-candidates'      => 'recruitment.view',
        'job-interviews'      => 'recruitment.view',
        'job-applications'    => 'recruitment.view',
        // Inventario
        'products'            => 'inventory.manage',
        'stock-movements'     => 'inventory.view',
        'product-stock'       => 'inventory.view',
        'categories'          => 'inventory.manage',
        'brands'              => 'inventory.manage',
        'warehouses'          => 'inventory.manage',
        'units'               => 'inventory.manage',
        // CRM
        'clients'             => 'clients.manage',
        'contacts'            => 'contacts.manage',
        'deals'               => 'deals.manage',
        'activities'          => 'activities.manage',
        'segments'            => 'segments.manage',
        // Compras
        'suppliers'           => 'purchases.manage',
        'purchase-orders'     => 'purchases.manage',
        'purchase-invoices'   => 'purchases.manage',
        'purchase-receipts'   => 'purchases.manage',
        'accounts-payable'    => 'purchases.view',
        // Ventas
        'quotes'              => 'sales.manage',
        'sale-orders'         => 'sales.manage',
        'sale-invoices'       => 'sales.manage',
        'accounts-receivable' => 'sales.view',
        // Finanzas
        'payments'               => 'finance.manage',
        'transfers'              => 'finance.manage',
        'cash-accounts'          => 'finance.manage',
        'financial-transactions' => 'finance.view',
        // Contabilidad
        'accounting-periods' => 'accounting.manage',
        'journal-entries'    => 'accounting.view',
        'chart-of-accounts'  => 'accounting.manage',
    ];

    public function __invoke(Request $request, string $resource, string $format, TableQueryService $tables)
    {
        $map = $this->map();
        abort_unless(array_key_exists($resource, $map), 404);
        abort_unless($request->user()->can($this->permissionByResource[$resource]), 403);

        [$model, $headers, $queryEnhancer, $rowMapper] = array_pad($map[$resource], 4, null);
        $query = $model::query()->where('company_id', $this->companyId($request));

        if ($queryEnhancer) {
            $queryEnhancer($query);
        }

        // Pass model-attribute columns to TableQueryService only when no rowMapper is used.
        // Mapped resources use readable header labels, not attribute names.
        $searchColumns = $rowMapper ? [] : $headers;
        $tables->apply($request, $query, $searchColumns, ['status' => 'status']);

        abort_unless(in_array($format, ['csv', 'pdf']), 404);

        $date     = now()->format('Y-m-d');
        $baseName = $this->resourceNames[$resource] ?? str_replace('-', '_', $resource);
        $filename = "{$baseName}_{$date}.{$format}";

        if ($format === 'pdf') {
            $rows = $rowMapper
                ? $query->limit(self::PDF_LIMIT)->get()
                : $query->limit(self::PDF_LIMIT)->get($headers);

            $truncated = $rows->count() >= self::PDF_LIMIT;

            $pdf = Pdf::loadView('exports.table', [
                'resource'  => $resource,
                'columns'   => $headers,
                'rows'      => $rows,
                'rowMapper' => $rowMapper,
                'truncated' => $truncated,
                'limit'     => self::PDF_LIMIT,
            ]);
            return $pdf->download($filename);
        }

        // CSV: chunk streaming — sin límite hardcodeado, eficiente en memoria
        $chunkSize = self::CSV_CHUNK;
        $callback  = function () use ($headers, $query, $rowMapper, $chunkSize): void {
            // ponytail: simple prefix guard against CSV formula injection (=, +, -, @, tab, CR)
            $sanitize = static fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v;

            $handle = fopen('php://output', 'w');
            fprintf($handle, "\xEF\xBB\xBF"); // UTF-8 BOM para Excel

            fputcsv($handle, $headers);

            $query->chunk($chunkSize, function ($chunk) use ($handle, $rowMapper, $headers, $sanitize) {
                foreach ($chunk as $row) {
                    $raw = $rowMapper
                        ? $rowMapper($row)
                        : collect($headers)->map(fn ($col) => $row->{$col})->all();
                    fputcsv($handle, array_map($sanitize, $raw));
                }
            });

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
