# PROJECT_STATE.md — Fotografía del proyecto
> Actualizar cuando cambie el estado real de un módulo. Fecha: 2026-10-04.

## Stack
| Capa | Tecnología |
|------|-----------|
| Backend | Laravel 12 · PHP 8.2+ · Sanctum Bearer · Spatie Permission |
| Frontend | Next.js 16 · App Router · TypeScript · TailwindCSS v4 · shadcn/ui |
| BD | SQLite (local/prod Hostinger con MySQL) |
| Auth | Sanctum token · `/auth/me` · guard frontend `/app` |

---

## Estado por módulo

### RRHH (Fase 0) ✅ COMPLETO

| Capa | Estado |
|------|--------|
| Backend | ✅ Controllers, Models, Migrations, FormRequests |
| BD | ✅ employees, departments, positions, attendances, vacation_requests, permission_requests, sick_leaves, employee_documents, shifts, shift_assignments |
| API | ✅ CRUD completo por recurso |
| Frontend | ✅ Páginas: empleados, asistencia, vacaciones, permisos, incapacidades, documentos, turnos |
| Permisos | ✅ employees.manage / attendance.manage / requests.approve / documents.manage |
| Export | ✅ CSV/PDF por módulo |
| Perfil empleado | ⚠️ Parcial — estructura visual, no todos los tabs cargan desde API |
| Tests | ❌ Sin tests automatizados |
| E2E | ❌ Sin E2E |

### CRM (Fase A) ✅ COMPLETO

| Capa | Estado |
|------|--------|
| Backend | ✅ Controllers: Lead, Client, ClientNote, Contact, Deal, Activity, Segment |
| BD | ✅ leads, clients, client_notes, contacts, deals, activities, segments |
| API | ✅ CRUD completo |
| Frontend | ✅ Páginas: leads, clientes, crm/contactos, crm/deals, crm/actividades, crm/segmentos |
| Permisos | ✅ crm.manage / crm.view / leads.view |
| Tests | ❌ Sin tests |
| E2E | ❌ Sin E2E |

### Inventario (Fase B) ✅ COMPLETO

| Capa | Estado |
|------|--------|
| Backend | ✅ Controllers: Product, Category, Brand, Unit, Warehouse, Stock, StockMovement |
| BD | ✅ products, categories, brands, units, warehouses, product_stock, stock_movements |
| API | ✅ CRUD + StockService (único punto de escritura a stock) |
| Frontend | ✅ Páginas: productos, categorías, marcas, unidades, bodegas, stock, movimientos |
| Permisos | ✅ inventory.manage / inventory.movements / inventory.view |
| StockService | ✅ entry/exit/adjustment/transfer · avg_cost · InvalidProductTypeException |
| Tests | ❌ Sin tests |
| E2E | ❌ Sin E2E |

### Compras (Fase C) ✅ COMPLETO

| Capa | Estado |
|------|--------|
| Backend — Models | ✅ Supplier, PurchaseOrder, PurchaseOrderItem, PurchaseReceipt, PurchaseReceiptItem, PurchaseInvoice, AccountPayable |
| BD — Migrations | ✅ Aplicadas: purchases_tables + purchases_permissions (guard_name=web) |
| Backend — Service | ✅ PurchaseService: postReceipt() → StockService.entry(), postInvoice() → AccountPayable |
| Backend — FormRequests | ✅ StoreSupplierRequest, StorePurchaseOrderRequest, StorePurchaseReceiptRequest, StorePurchaseInvoiceRequest |
| Backend — Resources | ✅ Supplier, PurchaseOrder, PurchaseReceipt, PurchaseInvoice, AccountPayable |
| Backend — Controllers | ✅ Supplier, PurchaseOrder, PurchaseReceipt, PurchaseInvoice, AccountPayable |
| API | ✅ Routes: /suppliers, /purchase-orders, /purchase-receipts (+ POST /post), /purchase-invoices (+ POST /post), /accounts-payable |
| Frontend — Types | ✅ Supplier, PurchaseOrder, PurchaseReceipt, PurchaseInvoice, AccountPayable en types.ts |
| Frontend — Páginas | ✅ /app/compras/proveedores, /ordenes, /recepciones, /facturas, /cxp |
| Frontend — UI Wizards (K9) | ✅ Modales de creación: OC (con líneas), Recepción (con líneas + auto-fill proveedor), Factura compra (flat form). Acciones: Postear recepción, Postear factura. Badges de estado, 422 per-field, mobile-first. |
| Frontend — Nav | ✅ Grupo "Compras" en admin-shell.tsx |
| Permisos | ✅ purchases.manage / purchases.view · guard=web · asignados a Super Admin y Administrador |
| StockService | ✅ PurchaseService usa StockService.entry() con reference_type='purchase_receipt' |
| Multitenancy | ✅ company_id en todas las tablas de cabecera |
| Tests | ✅ PurchasesTest 14/14 passed (5 nuevos casos 422/403) |
| Build | ✅ npm run build verde, TypeScript OK |
| E2E | ❌ Pendiente (no bloqueante para demo) |

### Ventas (Fase D) ✅ COMPLETO (2026-10-04)

| Capa | Estado |
|------|--------|
| Backend — Models | ✅ Quote, QuoteItem, SaleOrder, SaleOrderItem, SaleInvoice, SaleInvoiceItem, AccountsReceivable |
| BD — Migrations | ✅ sales_permissions + create_sales_tables + add_sale_order_id_to_deals |
| Backend — Service | ✅ SaleService: confirmOrder(), postInvoice() → StockService.exit() + AccountsReceivable, createReturn() |
| Backend — FormRequests | ✅ StoreQuoteRequest, StoreSaleOrderRequest, StoreSaleInvoiceRequest |
| Backend — Resources | ✅ Quote, SaleOrder, SaleInvoice, AccountsReceivable |
| Backend — Controllers | ✅ QuoteController, SaleOrderController (+ confirm), SaleInvoiceController (+ post + createReturn), AccountsReceivableController |
| API | ✅ /quotes, /sale-orders (+ POST /confirm), /sale-invoices (+ POST /post + POST /return), /accounts-receivable |
| CRM integration | ✅ Reutiliza clients — no duplicado |
| Inventario integration | ✅ StockService.exit() con reference_type='sale_invoice'; cost_at_time capturado |
| Deal → sale_order_id | ✅ FK nullable en deals + Deal.$fillable actualizado |
| Multitenancy | ✅ company_id en todos los documentos de cabecera |
| Permisos | ✅ sales.manage / sales.view · guard=web · asignados a Super Admin y Administrador |
| Frontend — Types | ✅ Quote, SaleOrder, SaleInvoice, AccountsReceivable en types.ts |
| Frontend — Páginas | ✅ /app/ventas/cotizaciones, /pedidos, /facturas, /cxc |
| Frontend — UI Wizards (K10) | ✅ Modales de creación: Cotización (con líneas), Pedido (con líneas + auto-fill cotización/cliente), Factura venta (con líneas). Acciones: Confirmar pedido, Postear factura. Badges de estado, 422 per-field, mobile-first. |
| Frontend — Nav | ✅ Grupo "Ventas" en admin-shell.tsx |
| Tests | ✅ SalesTest 19/19 passed (6 nuevos casos 422/403) |
| Build | ✅ npm run build verde, TypeScript OK |
| E2E | ❌ Pendiente (no bloqueante para demo) |

### Finanzas (Fase E) ✅ COMPLETO (2026-10-04)

| Capa | Estado |
|------|--------|
| Backend — Models | ✅ CashAccount, Payment, FinancialTransaction, Transfer |
| BD — Migrations | ✅ add_finance_permissions + create_finance_tables |
| Backend — Service | ✅ PaymentService: registerPayment, cancelPayment, transfer, cancelTransfer |
| Backend — FormRequests | ✅ StoreCashAccountRequest, StorePaymentRequest, StoreTransferRequest |
| Backend — Resources | ✅ CashAccount, Payment, FinancialTransaction, Transfer |
| Backend — Controllers | ✅ CashAccount, Payment (+ cancel), Transfer (+ cancel), FinancialTransaction (read-only) |
| API | ✅ /cash-accounts, /payments (+ cancel), /transfers (+ cancel), /financial-transactions |
| Permisos | ✅ finance.manage / finance.view · guard=web · asignados a Super Admin y Administrador |
| PaymentService | ✅ Único escritor de cash_accounts.balance y financial_transactions |
| Cancelación auditada | ✅ Crea transacciones compensatorias (NO elimina registros originales) |
| Gancho Contabilidad | ✅ payments.journal_entry_id nullable (Fase F) |
| Multitenancy | ✅ company_id en todas las tablas |
| Frontend — Types | ✅ CashAccount, Payment, FinancialTransaction, Transfer en types.ts |
| Frontend — Páginas | ✅ /app/finanzas/cuentas, /pagos, /transferencias, /movimientos |
| Frontend — Nav | ✅ Grupo "Finanzas" en admin-shell.tsx |
| Tests | ✅ FinanceTest 20/20 passed (57 assertions) |
| Build | ✅ npm run build verde, TypeScript OK |
| E2E | ❌ Pendiente (no bloqueante para demo) |

### Contabilidad (Fase F) ✅ COMPLETO (2026-10-04)

| Capa | Estado |
|------|--------|
| Backend — Models | ✅ ChartOfAccount, AccountingPeriod, JournalEntry, JournalEntryLine, AccountingAccountConfig |
| BD — Migrations | ✅ add_accounting_permissions + create_accounting_tables + add_accounting_hooks |
| Backend — Service | ✅ AccountingService: generateAndPost, postEntry, reverseEntry, openPeriod, closePeriod, createOpeningEntry |
| Integración C/D/E | ✅ PurchaseService, SaleService, PaymentService llaman AccountingService después de sus transacciones |
| Backend — FormRequests | ✅ StoreChartOfAccountRequest, StoreAccountingPeriodRequest, StoreAccountingAccountConfigRequest |
| Backend — Resources | ✅ ChartOfAccount, AccountingPeriod, JournalEntry, AccountingAccountConfig |
| Backend — Controllers | ✅ ChartOfAccount, AccountingPeriod (+ close), JournalEntry (+ reverse + post + opening), AccountingAccountConfig |
| API | ✅ /chart-of-accounts, /accounting-periods (+ close + opening), /journal-entries (+ reverse + post), /accounting-configs |
| Seeder | ✅ AccountingSeeder: 33 cuentas PUC funcional + 7 configs de integración |
| Partida doble | ✅ postEntry valida sum(debit) == sum(credit) |
| Ganchos | ✅ journal_entry_id en sale_invoices, purchase_invoices, transfers + accounting_account_id en cash_accounts |
| Multitenancy | ✅ company_id en todas las tablas contables |
| Permisos | ✅ accounting.view / manage / post / close · guard=web · asignados a Super Admin y Administrador |
| Reversión | ✅ Solo asientos posted · asiento compensatorio en período actual · NO elimina originales |
| Finanzas 100% manual | ✅ Ver D-FIN-6: ninguna integración bancaria externa |
| Frontend — Types | ✅ ChartOfAccount, AccountingPeriod, JournalEntry, JournalEntryLine, AccountingAccountConfig |
| Frontend — Páginas | ✅ /app/contabilidad/plan-cuentas, /periodos, /diario, /configuracion |
| Frontend — Nav | ✅ Grupo "Contabilidad" en admin-shell.tsx (4 ítems) |
| Tests | ✅ AccountingTest 20/20 passed (34 assertions) |
| Regresión | ✅ 99/99 tests passed (todo el suite) |
| Build | ✅ npm run build verde, TypeScript OK |
| E2E | ❌ Pendiente (no bloqueante para demo) |
| PUC oficial | ⚠️ Seeder es funcional de referencia — VALIDAR NORMATIVAMENTE antes de uso legal/fiscal |

---

### K4 — Smoke Tests SQLite + MySQL ✅ COMPLETO (2026-10-04)

| Motor | Migraciones | Seeders | Tests | Assertions | Incompatibilidades |
|-------|------------|---------|-------|------------|-------------------|
| SQLite (in-memory) | ✅ 50/50 | ✅ AccountingSeeder | ✅ 115/115 | 279 | ninguna |
| MySQL 9.5 (local port 3307) | ✅ 50/50 | ✅ AccountingSeeder | ✅ 115/115 | 279 | 1 bug resuelto (ver abajo) |

**Bug encontrado y resuelto:** `with('client:id,name')` en 4 controladores de Ventas generaba `SELECT id, name FROM clients` — columna `name` no existe en producción MySQL. SQLite lo ignoraba silenciosamente. Fix: `client:id,first_name,last_name` en controladores + `$this->client->full_name` en 4 resources (usa el accessor `getFullNameAttribute` del modelo).

**Archivos modificados en K4:**
- `QuoteController.php`, `SaleOrderController.php`, `SaleInvoiceController.php`, `AccountsReceivableController.php` — `$with` corregido
- `QuoteResource.php`, `SaleOrderResource.php`, `SaleInvoiceResource.php`, `AccountsReceivableResource.php` — accessor corregido

---

### Autorización (K1) ✅ COMPLETO (2026-10-04)

| Capa | Estado |
|------|--------|
| Multitenancy | ✅ BaseCrudController + todos los custom actions ya escopaban company_id |
| Route middleware | ✅ `can:X.manage` / `can:X.view` en todas las rutas sensibles |
| FK cross-company | ✅ BRECHA CERRADA — `ApiFormRequest::ownedExists()` scope a company_id en todos los FormRequests (17 archivos) |
| Laravel Policies | ✅ EmployeePolicy + StockMovementPolicy creadas y registradas en AppServiceProvider |
| Tests K1 | ✅ AuthorizationTest 5/5 passed — verifica rechazo de IDs de otra empresa |
| Suite total | ✅ 115/115 passed (279 assertions) |

**Brecha cerrada:** `exists:TABLE,id` sin `company_id` en FormRequests permitía pasar IDs de otra empresa en POST. Solucionado con helper `ownedExists()` en `ApiFormRequest` que aplica `Rule::exists()->where('company_id', $cid)` cuando hay usuario autenticado, con fallback seguro para el path update de BaseCrudController.

---

## Módulos core (siempre presentes)

| Módulo | Estado |
|--------|--------|
| Dashboard | ✅ Conectado a API real |
| Usuarios | ✅ CRUD (crear/editar/deshabilitar) |
| Roles | ✅ CRUD con permisos Spatie |
| Auditoría | ✅ Tabla de audit_logs con filtros |
| Reportes | ⚠️ Catálogo visual, no todos los reportes tabulares |
| IA | ⚠️ Interfaz lista, sin proveedor conectado |
| Contingencia | ✅ Modo offline con cola local y sincronización |
| Configuración | ⚠️ UI parcial |
| Organización | ✅ Departamentos y cargos |

---

## Sitio público de marketing

| Página | Estado |
|--------|--------|
| Inicio, Producto, Módulos, Soluciones, Precios, Nosotros, Blog, Contacto, Demo | ✅ Completo visualmente |
| Formulario de contacto | ⚠️ Visual, no envía datos |
| SEO (metadata, sitemap, robots) | ✅ |

---

## Cambios pendientes de commit (staging area actual)

- `backend/app/Models/` — Supplier, PurchaseOrder, PurchaseOrderItem, PurchaseReceipt
- `backend/database/migrations/` — add_purchases_permissions, create_purchases_tables
- `backend/app/Http/Controllers/Api/` — varios controllers RRHH con ajustes menores
- `frontend/src/app/app/` — crm/actividades, crm/deals, inventario/movimientos, inventario/productos
- `docs/arquitectura-fases-c-f.md`

---

## Despliegue

- **Backend prod:** Hostinger (SSH, MySQL, PHP 8.4 handler) — ver `docs/hostinger-deployment.md`
- **Frontend prod:** Pendiente configuración en Hostinger o Vercel
