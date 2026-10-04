# VERIFICATION_STATUS.md — Estado de verificación por módulo

> Actualizar al completar o verificar cada módulo. Fecha base: 2026-10-04.

## Leyenda
- ✅ Verificado funcionando
- ⚠️ Parcial / con advertencia conocida
- ❌ No implementado / roto
- — No aplica

---

## RRHH (Fase 0)

| Verificación | Estado | Notas |
|--------------|--------|-------|
| `migrate:fresh --seed` | ✅ | Pasa sin errores |
| Login demo (5 roles) | ✅ | Todos los usuarios demo funcionan |
| CRUD Empleados | ✅ | Crear/editar/deshabilitar |
| CRUD Departamentos y Cargos | ✅ | |
| Asistencia (lista + filtros) | ✅ | |
| Vacaciones (solicitud/aprobación) | ✅ | |
| Permisos laborales | ✅ | |
| Incapacidades | ✅ | |
| Documentos por empleado | ✅ | |
| Turnos + asignación | ✅ | |
| Auditoría (tabla) | ✅ | |
| Export CSV/PDF | ✅ | |
| Perfil empleado (tabs) | ⚠️ | No todos los tabs cargan API |
| Guard `/app` | ✅ | Redirige a login si no autenticado |
| Nav filtrado por permisos | ✅ | |
| Responsive móvil | ✅ | |
| `npm run build` sin errores | ✅ | |
| Tests | ❌ | |
| E2E | ❌ | |

---

## CRM (Fase A)

| Verificación | Estado | Notas |
|--------------|--------|-------|
| Leads (lista + CRUD) | ✅ | |
| Clientes (lista + CRUD) | ✅ | |
| Contactos | ✅ | |
| Deals (pipeline) | ✅ | Kanban pendiente |
| Actividades | ✅ | |
| Segmentos | ✅ | |
| Permisos crm.manage / crm.view | ✅ | |
| Responsive | ✅ | |
| Tests | ❌ | |
| E2E | ❌ | |

---

## Inventario (Fase B)

| Verificación | Estado | Notas |
|--------------|--------|-------|
| Productos (lista + CRUD) | ✅ | |
| Categorías, Marcas, Unidades | ✅ | |
| Bodegas (Warehouses) | ✅ | |
| Stock (por producto/bodega) | ✅ | |
| Movimientos (entrada/salida/ajuste/transferencia) | ✅ | |
| StockService (avg_cost, InvalidProductTypeException) | ✅ | |
| Permisos inventory.manage / inventory.movements / inventory.view | ✅ | |
| Responsive | ✅ | |
| Tests | ❌ | |
| E2E | ❌ | |

---

## Compras (Fase C) ✅ COMPLETO (2026-10-04)

| Verificación | Estado | Notas |
|--------------|--------|-------|
| Modelos (7 modelos completos) | ✅ | Supplier, PO, POItem, PurchaseReceipt, PurchaseReceiptItem, PurchaseInvoice, AccountPayable |
| Migraciones + permissions (guard=web) | ✅ | migrate:fresh --seed OK |
| PurchaseService (postReceipt + postInvoice) | ✅ | Usa StockService.entry() |
| FormRequests (4) | ✅ | StoreSupplier/PO/PurchaseReceipt/PurchaseInvoice · due_date=required (coherente con BD NOT NULL) |
| Resources (5) | ✅ | Supplier, PO, PurchaseReceipt, PurchaseInvoice, AccountPayable |
| Controllers (5) | ✅ | Incl. POST /{id}/post en Recepción y Factura |
| Rutas en api.php | ✅ | /suppliers, /purchase-orders, /purchase-receipts, /purchase-invoices, /accounts-payable |
| Multitenancy company_id | ✅ | Todas las tablas de cabecera |
| StockService respetado (sin escritura directa) | ✅ | reference_type='purchase_receipt' |
| Frontend — 5 páginas + nav | ✅ | Proveedores con CRUD; Órdenes/Recepciones/Facturas/CxP en modo lista |
| Permisos purchases.manage / purchases.view | ✅ | Asignados a Super Admin y Administrador |
| Tests (PurchasesTest) | ✅ | 9/9 passed, 15 assertions |
| Build TypeScript | ✅ | npm run build verde |
| E2E | ❌ | Pendiente (no bloqueante para demo) |

---

## Ventas (Fase D) ✅ COMPLETO (2026-10-04)

| Verificación | Estado | Notas |
|--------------|--------|-------|
| Modelos (7 modelos) | ✅ | Quote, QuoteItem, SaleOrder, SaleOrderItem, SaleInvoice, SaleInvoiceItem, AccountsReceivable |
| Migraciones + permissions (guard=web) | ✅ | migrate:fresh --seed OK |
| SaleService (confirmOrder + postInvoice + createReturn) | ✅ | StockService.exit() / cost_at_time / CxC |
| FormRequests (3) | ✅ | StoreQuote / StoreSaleOrder / StoreSaleInvoice |
| Resources (4) | ✅ | Quote, SaleOrder, SaleInvoice, AccountsReceivable |
| Controllers (4) | ✅ | Quote, SaleOrder (+ confirm), SaleInvoice (+ post + return), AccountsReceivable |
| Rutas en api.php | ✅ | /quotes, /sale-orders, /sale-invoices, /accounts-receivable |
| Deal → sale_order_id FK | ✅ | FK nullable en deals |
| CRM integration | ✅ | Clientes reutilizados, sin duplicación |
| Inventario integration | ✅ | StockService.exit() + cost_at_time inmutable |
| CxC generada al postear | ✅ | AccountsReceivable creada por SaleService |
| Multitenancy company_id | ✅ | Todas las tablas de cabecera |
| Frontend — 4 páginas + nav | ✅ | Cotizaciones/Pedidos/Facturas/CxC en modo lista |
| Permisos sales.manage / sales.view | ✅ | Asignados a Super Admin y Administrador |
| Tests (SalesTest) | ✅ | 13/13 passed, 25 assertions |
| Build TypeScript | ✅ | npm run build verde |
| E2E | ❌ | Pendiente (no bloqueante para demo) |

---

## Finanzas (Fase E) ✅ COMPLETO (2026-10-04)

| Verificación | Estado | Notas |
|--------------|--------|-------|
| Modelos (4) | ✅ | CashAccount, Payment, FinancialTransaction, Transfer |
| Migraciones + permissions (guard=web) | ✅ | migrate:fresh --seed OK |
| PaymentService (4 operaciones) | ✅ | registerPayment, cancelPayment, transfer, cancelTransfer |
| FormRequests (3) | ✅ | StoreCashAccount, StorePayment, StoreTransfer |
| Resources (4) | ✅ | CashAccount, Payment, FinancialTransaction, Transfer |
| Controllers (4) | ✅ | CashAccount, Payment (+ cancel), Transfer (+ cancel), FinancialTransaction (read-only) |
| Rutas en api.php | ✅ | /cash-accounts, /payments, /transfers, /financial-transactions |
| No saldos negativos (D-FIN-2) | ✅ | 422 si la operación deja balance < 0 |
| Cancelación compensatoria (D-FIN-3) | ✅ | Crea transacciones inversas, no borra las originales |
| Gancho Contabilidad (D-FIN-5) | ✅ | payments.journal_entry_id nullable |
| Permisos finance.manage / finance.view | ✅ | Asignados a Super Admin y Administrador |
| Frontend — 4 páginas + nav | ✅ | /cuentas, /pagos, /transferencias, /movimientos |
| Multitenancy company_id | ✅ | Todas las tablas |
| Tests (FinanceTest) | ✅ | 20/20 passed, 57 assertions |
| Build TypeScript | ✅ | npm run build verde |
| Tests de regresión (Compras+Ventas) | ✅ | 42/42 passed, 97 assertions totales |
| E2E | ❌ | Pendiente (no bloqueante para demo) |

---

## Build general

| Verificación | Estado | Fecha |
|--------------|--------|-------|
| `php artisan migrate:fresh --seed` | ✅ | 2026-10-04 |
| `npm run build` | ✅ | 2026-10-04 |
| Tests totales (Finanzas+Compras+Ventas) | ✅ | 42/42 passed, 97 assertions — 2026-10-04 |
| Deploy Hostinger backend | ✅ | ver `docs/hostinger-deployment.md` |
| Deploy frontend | ⚠️ | Pendiente configuración |
