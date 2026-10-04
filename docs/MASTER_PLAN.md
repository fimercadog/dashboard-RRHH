# MASTER_PLAN.md — Plan maestro de ejecución
> Creado: 2026-10-04. Basado en la auditoría completa del proyecto.
> **Leer antes de ejecutar cualquier fase.**

---

## Modelo conceptual

```
ERP BASE (este proyecto como plantilla maestra)
├── Infraestructura core
├── RRHH / Talento Humano
├── CRM
├── Inventario
├── Compras
├── Ventas
├── Finanzas
├── Contabilidad
├── Usuarios / Roles / Permisos / Policies
├── Auditoría
├── Configuración
└── Tests / E2E / Quality Gate

+

FUNCIONALIDADES ESPECÍFICAS DE INDUSTRIA
(Odontología, Veterinaria, HVAC, Educación…)

=

NUEVA VERTICAL
```

Un ERP BASE terminado es la condición previa para cualquier vertical. Las verticales reutilizan todo el ERP BASE y solo añaden lo que es exclusivo de su industria.

---

## Estado de partida (2026-10-04)

| Módulo | Estado |
|--------|--------|
| Core infraestructura | ✅ |
| RRHH | ✅ con 3 brechas menores |
| CRM | ✅ con 1 brecha (Kanban) |
| Inventario | ✅ |
| Compras | ✅ completo — 9/9 tests · build verde · deuda: wizard UI para documentos con líneas (K9) |
| Ventas | ❌ 0% |
| Finanzas | ❌ 0% |
| Contabilidad | ❌ 0% |
| Configuración | ⚠️ Parcial |
| Policies por recurso | ❌ 0% |
| Tests | ❌ 0% |
| E2E | ❌ 0% |
| Quality Gate automatizado | ❌ 0% |

---

## Dependencias entre fases

```
FASE 0 (fixes RRHH/CRM/INV)  — sin dependencias externas
FASE 1 (Compras)              — depende de: Inventario ✅
FASE 2 (Ventas)               — depende de: Inventario ✅ + CRM ✅
FASE 3 (Finanzas)             — depende de: Compras (Fase 1) + Ventas (Fase 2)
FASE 4 (Contabilidad)         — depende de: todo lo anterior
FASE 5 (Policies)             — puede ir en paralelo desde Fase 0
FASE 6 (Tests)                — puede empezar en Fase 0, crecer con cada fase
FASE 7 (E2E)                  — puede empezar después de Fase 2
FASE 8 (Quality Gate)         — define el criterio final de DONE
FASE 9 (Optimización)         — DESPUÉS de tests, nunca antes
FASE 10 (Ajustes)             — bajo demanda
FASE 11 (Docs consolidación)  — puede ir en paralelo
FASE 12 (Sistema verticales)  — después de que el ERP BASE esté completo
```

**Trabajo paralelo posible:**
- Fase 1 (Compras) y Fase 2 (Ventas) pueden desarrollarse en paralelo
- Fase 5 (Policies) puede avanzar en paralelo con cualquier fase
- Fase 6 (Tests) puede empezar en Fase 0 y crecer incrementalmente
- Fase 11 (Docs) puede ir en paralelo siempre

---

## FASE 0 — Cerrar brechas del ERP actual

**Objetivo:** Dejar RRHH, CRM e Inventario sin brechas conocidas antes de avanzar.

### Backend
- [ ] Revisar que todos los controllers tengan FormRequest (solo User y Role tienen pendientes)
- [ ] Crear `StoreUserRequest`, `StoreRoleRequest` si no existen

### BD
- [ ] Nada — las tablas existentes están bien

### Frontend
- [ ] Crear `src/app/app/empleados/[id]/page.tsx` — **el archivo no existe, es un bug activo**
- [ ] Extraer `STATUS_OPTIONS` a `src/lib/constants.ts`
- [ ] Extraer `IDENTIFICATION_TYPE_OPTIONS` a `src/lib/constants.ts`
- [ ] Actualizar empleados/page.tsx y clientes/page.tsx para usar las constantes

### Permisos
- [ ] Nada nuevo

### Auditoría
- [ ] Nada nuevo

### Tests
- [ ] Crear primer test de humo: `migrate:fresh --seed` pasa en SQLite Y MySQL

**Archivos afectados:**
- `src/app/app/empleados/[id]/page.tsx` (nuevo)
- `src/lib/constants.ts` (nuevo)
- `src/app/app/empleados/page.tsx` (actualizar import)
- `src/app/app/clientes/page.tsx` (actualizar import)

**Riesgos:** ninguno — son adiciones sin tocar lógica existente

**Criterio de DONE:**
- [ ] `/app/empleados/[id]` existe y carga datos del empleado desde API
- [ ] Las constantes están centralizadas
- [ ] `migrate:fresh --seed` pasa sin errores
- [ ] `npm run build` pasa sin errores TypeScript

---

## FASE 1 — Compras (Fase C del roadmap)

**Objetivo:** Módulo completo de proveedores, órdenes de compra, recepciones y facturas de compra, integrado con StockService e inventario.

**Referencia de diseño:** `docs/arquitectura-fases-c-f.md` — OBLIGATORIO leer antes de implementar.

### BD
- [ ] Commit migraciones existentes: `purchases_tables`, `purchases_permissions`
- [ ] Crear migración para `purchase_invoices`
- [ ] Crear migración para `accounts_payable`

### Backend — Modelos
- [ ] Commit modelos existentes: Supplier, PurchaseOrder, PurchaseOrderItem, PurchaseReceipt
- [ ] Crear `PurchaseInvoice` model
- [ ] Crear `AccountsPayable` model

### Backend — Servicios
- [ ] Crear `PurchaseService`:
  - `confirmOrder(PurchaseOrder)` → status draft→sent
  - `createReceipt(PurchaseOrder, items[])` → crea PurchaseReceipt + llama `StockService::entry(reference_type='purchase_receipt')`
  - `postInvoice(PurchaseInvoice)` → status draft→posted + crea AccountsPayable

### Backend — FormRequests
- [ ] `StoreSupplierRequest`
- [ ] `StorePurchaseOrderRequest`
- [ ] `StorePurchaseReceiptRequest`
- [ ] `StorePurchaseInvoiceRequest`

### Backend — Resources JSON
- [ ] `SupplierResource`
- [ ] `PurchaseOrderResource`
- [ ] `PurchaseReceiptResource`
- [ ] `PurchaseInvoiceResource`
- [ ] `AccountsPayableResource`

### Backend — Controllers
- [ ] `SupplierController` (extiende BaseCrudController)
- [ ] `PurchaseOrderController` (extiende Base + acciones: confirm, createReceipt)
- [ ] `PurchaseReceiptController` (extiende Base + post action)
- [ ] `PurchaseInvoiceController` (extiende Base + post action, usa PurchaseService)
- [ ] `AccountsPayableController` (extiende BaseCrudController, read-only principal)

### API — Rutas
- [ ] Registrar todos los recursos en `routes/api.php` dentro del grupo Sanctum

### Frontend
- [ ] `/app/compras` — layout/index con subnavegación
- [ ] `/app/compras/proveedores` — CRUD con ModuleTablePage
- [ ] `/app/compras/ordenes` — CRUD + acciones: confirmar, crear recepción
- [ ] `/app/compras/recepciones` — listado + crear recepción manual
- [ ] `/app/compras/facturas` — listado + acciones: postear factura
- [ ] Link en admin-shell nav bajo "Compras"
- [ ] Tipos en `src/lib/types.ts`: Supplier, PurchaseOrder, PurchaseReceipt, PurchaseInvoice, AccountsPayable

### Permisos
- [ ] Verificar migración `add_purchases_permissions` tiene: `purchases.manage`, `purchases.view`
- [ ] Actualizar seeder de roles para asignar permisos según rol

### Auditoría
- [ ] Automática vía BaseCrudController para CRUDs
- [ ] Registrar manualmente en PurchaseService las acciones: post_invoice, create_receipt

### Tests
- [ ] Test: crear PurchaseReceipt → StockService::entry ejecutado → product_stock aumentado
- [ ] Test: postear PurchaseInvoice → AccountsPayable creado con balance = total

**Dependencias:** Inventario ✅ (StockService disponible)

**Archivos afectados:** ~15 nuevos (modelos, controllers, resources, migrations, pages, types)

**Riesgos:**
- PurchaseService llama a StockService — verificar que reference_type='purchase_receipt' está en los tipos aceptados
- accounts_payable necesita FK a companies y suppliers correctamente

**Criterio de DONE:**
- [ ] `migrate:fresh --seed` pasa sin errores
- [ ] `npm run build` pasa sin errores TypeScript
- [ ] Flujo completo funciona en browser: proveedor → OC → recepción → stock aumenta → factura → CxP creada
- [ ] Los permisos están correctamente asignados por rol
- [ ] Tests de integración pasan

---

## FASE 2 — Ventas (Fase D del roadmap) ✅ COMPLETO (2026-10-04)

**Objetivo:** Módulo completo de cotizaciones, pedidos, facturación de venta, con integración CRM (Deals) e Inventario (StockService::exit).

**Referencia de diseño:** `docs/arquitectura-fases-c-f.md`

### BD
- [x] Migración `add_sales_permissions`
- [x] Migración `create_sales_tables`: quotes, quote_items, sale_orders, sale_order_items, sale_invoices, sale_invoice_items, accounts_receivable

### Backend — Modelos
- [x] Quote, QuoteItem, SaleOrder, SaleOrderItem, SaleInvoice, SaleInvoiceItem, AccountsReceivable

### Backend — Servicios
- [x] `SaleService`:
  - `confirmOrder(SaleOrder)` → status confirmed
  - `postInvoice(SaleInvoice)` → llama `StockService::exit(reference_type='sale_invoice')` + captura `cost_at_time` desde `avg_cost` + crea AccountsReceivable
  - `createReturn(SaleInvoice)` → type=return + StockService::entry(reference_type='sale_return')

### Backend — FormRequests, Resources, Controllers
- [x] StoreQuoteRequest, StoreSaleOrderRequest, StoreSaleInvoiceRequest
- [x] Resources para cada entidad
- [x] Controllers: QuoteController, SaleOrderController, SaleInvoiceController, AccountsReceivableController

### API — Rutas
- [x] Registrar todos en api.php

### Frontend
- [x] `/app/ventas/cotizaciones`
- [x] `/app/ventas/pedidos`
- [x] `/app/ventas/facturas`
- [x] `/app/ventas/cxc` (cuentas por cobrar)
- [x] Link en nav bajo "Ventas"
- [x] Tipos en types.ts: Quote, SaleOrder, SaleInvoice, AccountsReceivable

### Integración CRM
- [x] Deal → campo `sale_order_id` (nullable) para vincular oportunidad ganada con pedido de venta

### Permisos
- [x] `sales.manage`, `sales.view` en migración
- [x] Asignados a Super Admin y Administrador

### Auditoría
- [x] Automática vía BaseCrudController
- [x] Manual en SaleService (post, return)

### Tests
- [x] 13/13 passed, 25 assertions (SalesTest.php)
- [x] postear SaleInvoice → StockService::exit → stock disminuido → cost_at_time capturado
- [x] crear devolución → StockService::entry inverso

**Criterio de DONE:**
- [x] `migrate:fresh --seed` pasa
- [x] `npm run build` pasa
- [x] Flujo completo: cotización → pedido → factura → stock disminuye → CxC creada
- [x] Vinculación con Deal CRM funciona (sale_order_id FK)
- [x] Tests pasan

---

## FASE 3 — Finanzas (Fase E del roadmap)

**Objetivo:** Gestión de caja/bancos, registro de pagos (cobros a clientes y pagos a proveedores), integración con CxC y CxP.

**Referencia de diseño:** `docs/arquitectura-fases-c-f.md`

### BD
- [ ] Migración `add_finance_permissions`
- [ ] Migración `create_finance_tables`: cash_accounts, payments, financial_transactions

### Backend — Modelos
- [ ] CashAccount, Payment, FinancialTransaction

### Backend — Servicios
- [ ] `PaymentService`:
  - `registerPayment(payable_type, payable_id, amount, cash_account_id)`:
    1. Decrementa `cash_accounts.balance`
    2. Crea `financial_transaction`
    3. Decrementa `accounts_receivable.balance` (cobro) o `accounts_payable.balance` (pago)
    4. Si `balance == 0` → `status = paid`; si `balance < original` → `partially_paid`
  - `cancelPayment(Payment)` → reversa todos los pasos anteriores

### Backend — FormRequests, Resources, Controllers
- [ ] StoreCashAccountRequest, StorePaymentRequest
- [ ] Resources para cada entidad
- [ ] Controllers: CashAccountController, PaymentController, FinancialTransactionController

### Frontend
- [ ] `/app/finanzas/cuentas` — CRUD de cuentas de caja/banco
- [ ] `/app/finanzas/pagos` — registrar pagos (cobros + pagos a proveedores)
- [ ] `/app/finanzas/transacciones` — listado de movimientos
- [ ] Link en nav bajo "Finanzas"
- [ ] Tipos en types.ts: CashAccount, Payment, FinancialTransaction

### Permisos
- [ ] `finance.manage`, `finance.view`

### Tests
- [ ] Test: registrar pago → accounts_receivable.balance decrementado
- [ ] Test: pago completa la deuda → status=paid
- [ ] Test: pago parcial → status=partially_paid

**Dependencias:** Compras (Fase 1) ✅ + Ventas (Fase 2) ✅

**Criterio de DONE:**
- [ ] Flujo completo: factura venta → cobro → saldo CxC actualizado → caja reducida
- [ ] Flujo: factura compra → pago → saldo CxP actualizado → caja reducida
- [ ] Tests pasan

---

## FASE 4 — Contabilidad (Fase F del roadmap)

**Objetivo:** Plan de cuentas, períodos contables, asientos automáticos generados por los eventos de Compras/Ventas/Finanzas/Inventario.

**Referencia de diseño:** `docs/arquitectura-fases-c-f.md` — Sección 3 (tablas) y regla de asientos por documento.

### BD
- [ ] Migración `add_accounting_permissions`
- [ ] Migración `create_accounting_tables`: chart_of_accounts, accounting_periods, journal_entries, journal_entry_lines

### Backend — Modelos
- [ ] ChartOfAccount, AccountingPeriod, JournalEntry, JournalEntryLine

### Backend — Servicios
- [ ] `AccountingService`:
  - `generateEntry(reference_type, reference_id)` → crea journal_entry + lines según el tipo de documento
  - Disparado por: PurchaseService::postInvoice, SaleService::postInvoice, PaymentService::registerPayment
  - Al postear entry: actualiza `stock_movements.journal_entry_id` y `posted_at` (los ganchos ya están en BD)

### Backend — FormRequests, Resources, Controllers
- [ ] StoreChartOfAccountRequest, StoreAccountingPeriodRequest
- [ ] Resources para cada entidad
- [ ] Controllers: ChartOfAccountController, AccountingPeriodController, JournalEntryController

### Frontend
- [ ] `/app/contabilidad/plan-cuentas` — árbol de cuentas con jerarquía
- [ ] `/app/contabilidad/periodos` — gestión de períodos contables (abrir/cerrar)
- [ ] `/app/contabilidad/diario` — listado de asientos
- [ ] `/app/contabilidad/mayor` — vista por cuenta (opcional, puede ser reporte)
- [ ] Link en nav bajo "Contabilidad"

### Permisos
- [ ] `accounting.manage`, `accounting.view`, `accounting.close_period`

### Integración de ganchos (ya en BD)
- [ ] `stock_movements.journal_entry_id` → rellenar al generar asiento de movimiento
- [ ] `stock_movements.posted_at` → rellenar al postear asiento
- [ ] `products.inventory_account_code / cogs_account_code / sale_account_code` → usar en AccountingService

### ⚠️ Gaps del diseño a señalar antes de implementar
La arquitectura actual (`arquitectura-fases-c-f.md`) NO especifica:
- ¿Cómo se manejan asientos de apertura de período?
- ¿Hay balance de comprobación automatizado o es un reporte?
- ¿El cierre de período bloquea la creación de asientos en ese período?

**ESTOS PUNTOS DEBEN RESPONDERSE ANTES DE ESCRIBIR AccountingService.**

### Tests
- [ ] Test: postear factura venta → journal_entry creado con líneas correctas (debe = CxC, haber = Ingresos)
- [ ] Test: postear factura compra → journal_entry creado (debe = Inventario, haber = CxP)
- [ ] Test: registrar pago → journal_entry creado (debe = CxP, haber = Caja)

**Dependencias:** Compras (F1) + Ventas (F2) + Finanzas (F3) ✅

**Criterio de DONE:**
- [ ] Asientos se generan automáticamente al postear documentos
- [ ] `stock_movements.journal_entry_id` y `posted_at` se rellenan correctamente
- [ ] Los 3 puntos gap de arriba están respondidos e implementados
- [ ] Tests pasan

---

## FASE 5 — Policies y autorización fina

**Objetivo:** Que un usuario con `employees.manage` solo pueda editar empleados de SU empresa (y no de otra).

**Nota:** El multitenant por `company_id` ya protege esto en BaseCrudController via `where('company_id', $companyId)`. Las policies son necesarias para casos más finos: ¿puede un Supervisor ver empleados de otro departamento?

### Backend
- [ ] `EmployeePolicy` — reglas de ownership por company_id (ya cubierto) + por departamento si aplica
- [ ] `StockMovementPolicy` — solo inventory.manage puede crear movimientos manuales
- [ ] Registrar policies en `AuthServiceProvider`

### Frontend
- [ ] Pantalla 403 dedicada para rutas profundas sin permiso

**Dependencias:** ninguna — puede ir en paralelo

**Criterio de DONE:**
- [ ] Un usuario de empresa A no puede ver/editar registros de empresa B
- [ ] Tests de autorización pasan

---

## FASE 6 — Tests de integración

**Objetivo:** Red de seguridad que detecte regresiones antes de declarar cualquier fase como DONE.

### Flujos críticos (prioridad alta)
1. Auth: login por cada rol → permisos correctos
2. StockService: entry/exit/adjustment/transfer → product_stock.quantity y avg_cost correctos
3. Compras: OC → recepción → stock aumenta → factura → CxP creada
4. Ventas: cotización → pedido → factura → stock disminuye → CxC creada
5. Finanzas: cobro → CxC.balance decrementado → status=paid cuando balance=0
6. Contabilidad: postear factura → journal_entry generado con partida doble balanceada

### Framework
- Backend: Pest (ya instalado con Laravel)
- Frontend: ninguno por ahora (declarar E2E como la capa de tests de UI)

**Criterio de DONE:**
- [ ] Los 6 flujos críticos tienen tests que pasan en SQLite Y en MySQL

---

## FASE 7 — E2E (Playwright)

**Objetivo:** Verificar que los flujos críticos funcionan de principio a fin en el navegador.

### Flujos a cubrir
1. Login con cada rol → ver/no ver módulos correctos
2. CRUD de empleado (crear → editar → deshabilitar)
3. Movimiento de stock manual
4. Crear OC → recepción → ver stock actualizado
5. Crear factura venta → ver CxC creada
6. Registrar cobro → ver CxC con balance actualizado
7. Responsive: los 7 flujos anteriores en viewport 375px

**Criterio de DONE:**
- [ ] Los 7 flujos pasan en Chromium desktop
- [ ] Los 7 flujos pasan en Chromium móvil (375px)

---

## FASE 8 — Quality Gate automatizado

**Objetivo:** Un comando único que certifica que el ERP está en estado entregable.

### Composición del Quality Gate

```bash
# QG completo — ejecutar antes de declarar DONE
php artisan migrate:fresh --seed              # BD limpia con datos demo
php artisan migrate:fresh --seed --env=testing # con MySQL si está configurado
php artisan test --coverage                   # tests Pest + cobertura
npm run build                                 # TypeScript sin errores
npx playwright test                           # E2E críticos
```

**El Quality Gate NO puede declarar DONE si cualquiera de los pasos falla.**

### Script de QG
- [ ] Crear `scripts/quality-gate.sh` (o `.ps1`) que ejecute los pasos en orden y reporte PASS/FAIL por paso
- [ ] Documentar en `DEVELOPMENT_WORKFLOW.md`

**Criterio de DONE:**
- [ ] El script existe y ejecuta todos los pasos
- [ ] El script falla (exit code != 0) si cualquier paso falla
- [ ] El script pasa en un entorno limpio

---

## FASE 9 — Optimización

**Objetivo:** Reducir duplicación y complejidad SIN perder funcionalidad ni cobertura de tests.

**REGLA: Solo ejecutar después de que los tests pasen.**

### Tareas identificadas
- [ ] Extraer `STATUS_OPTIONS` + `IDENTIFICATION_TYPE_OPTIONS` a `src/lib/constants.ts` (impacto: 11 archivos)
- [ ] Revisar `UpdateEmployeeRequest` vs `StoreEmployeeRequest` — determinar si pueden consolidarse
- [ ] Auditar queries N+1 en DashboardController (260 líneas, muchas consultas individuales)
- [ ] Verificar que todos los `with()` en controllers cargan lo necesario y no más

**Lo que NO se toca:**
- BaseCrudController, StockService, ModuleTablePage — perfecto como están
- Controllers de 14-15 líneas — es el tamaño correcto, no hay que "mejorar"
- DataTable, CrudModal — funcionan bien

---

## FASE 10 — Ajustes funcionales pequeños

Bajo demanda de Fidel. Ejemplos de backlog actual:
- Kanban para Deal pipeline en CRM
- Perfil de empleado completo con todos los tabs
- Formulario de contacto público con endpoint
- Reportes tabulares dedicados
- IA con proveedor conectado

Cada ajuste sigue: Analizar → Implementar → Verificar → Documentar si genera regla.

---

## FASE 11 — Consolidación de documentación

- [ ] Agregar nota en `docs/architecture.md` → "Ver ARCHITECTURE.md"
- [ ] Actualizar `docs/database.md` con tablas CRM/Inventario/Compras/Ventas/Finanzas/Contabilidad
- [ ] Actualizar `docs/api.md` con todos los endpoints actuales
- [ ] Actualizar `docs/development-status.md` para reflejar el estado real post-Fase 4
- [ ] Actualizar `docs/modules.md` y `docs/roles-permissions.md`

---

## FASE 12 — Sistema de replicación de verticales

**Objetivo:** Que al decir "Construye la vertical X", Claude pueda ejecutar el proceso completo sin instrucciones manuales.

### Artefactos a crear

**`docs/VERTICAL_TEMPLATE.md`** — Plantilla que Claude llena para cualquier nueva vertical:
```
Vertical: [nombre]
Industria: [descripción]
Módulos del ERP BASE que reutiliza: [lista]
Funcionalidades específicas: [lista]
Tablas nuevas: [lista con columnas y FKs]
Servicios nuevos: [lista]
Permisos nuevos: [lista]
Controllers nuevos: [lista]
Páginas frontend nuevas: [lista]
Tests específicos: [lista]
Integraciones con módulos core: [descripción]
Quality Gate resultado: [PASS/FAIL por paso]
```

**`docs/VERTICAL_CHECKLIST.md`** — El checklist exhaustivo que Claude ejecuta para cada vertical:
```
PRE-IMPLEMENTACIÓN
[ ] Leer CORE_CONTRACT.md
[ ] Leer PROJECT_STATE.md
[ ] Leer VERTICALS.md
[ ] Leer DEVELOPMENT_WORKFLOW.md
[ ] Leer DEVELOPMENT_MEMORY.md (M1–M11+)
[ ] Leer arquitectura-fases-c-f.md si la vertical tiene compras/ventas/finanzas
[ ] Analizar una vertical existente como referencia
[ ] Crear VERTICAL_TEMPLATE con la vertical nueva
[ ] Identificar qué existe en ERP BASE que se reutiliza
[ ] Identificar qué es específico de la industria
[ ] Validar que no se duplica nada del core

IMPLEMENTACIÓN (por capa)
[ ] Migración de permisos → PRIMERO
[ ] Migraciones de tablas (con company_id)
[ ] Modelos Eloquent (fillable, casts, relaciones)
[ ] Services si aplica (NUNCA escribir stock directamente)
[ ] FormRequests (StoreXxxRequest)
[ ] Resources JSON
[ ] Controllers (extienden BaseCrudController)
[ ] Rutas en api.php
[ ] Tipos en frontend/src/lib/types.ts
[ ] Páginas frontend con ModuleTablePage
[ ] Links en admin-shell nav
[ ] Auditoría verificada

VERIFICACIÓN
[ ] migrate:fresh --seed pasa
[ ] npm run build pasa
[ ] Tests de flujos críticos de esta vertical pasan
[ ] E2E críticos de esta vertical pasan
[ ] Responsive verificado en 375px
[ ] Permisos asignados correctamente por rol
[ ] Ningún módulo existente roto
[ ] CORE_CONTRACT no fue modificado sin autorización
[ ] Quality Gate completo: PASS

POST-IMPLEMENTACIÓN
[ ] PROJECT_STATE.md actualizado
[ ] VERTICALS.md actualizado con la nueva vertical
[ ] VERIFICATION_STATUS.md actualizado
[ ] DEVELOPMENT_MEMORY.md actualizado si hay nuevo aprendizaje
```

**Criterio de DONE:**
- [ ] Un desarrollador (o Claude) puede seguir el VERTICAL_CHECKLIST de principio a fin
- [ ] La primera vertical externa (ej. odontología) se construye sin instrucciones adicionales

---

## Quality Gate — definición completa

Un módulo/fase solo puede declararse DONE cuando pasa TODOS estos checks:

```
[ ] Backend completo (migración + modelo + service si aplica + FormRequest + resource + controller + rutas)
[ ] BD correcta (company_id presente, FKs correctas, no modifica tablas del core)
[ ] API funcional (todos los endpoints responden correctamente)
[ ] Frontend completo (página con ModuleTablePage + columns + fields + link en nav)
[ ] Permisos (migración de permisos + asignados a roles correctos)
[ ] Auditoría (BaseCrudController la hace automática; acciones especiales la registran manualmente)
[ ] Responsive verificado en viewport 375px
[ ] Loading state funciona
[ ] Error state funciona
[ ] Empty state funciona
[ ] Tests de flujos críticos pasan (PHP + SQLite)
[ ] Tests pasan en MySQL (si está configurado)
[ ] E2E flujos críticos pasan (cuando Playwright esté configurado)
[ ] npm run build pasa sin errores TypeScript
[ ] migrate:fresh --seed pasa sin errores
[ ] Ningún módulo existente roto
[ ] CORE no fue modificado sin justificación explícita
[ ] PROJECT_STATE.md actualizado
[ ] DEVELOPMENT_MEMORY.md actualizado si hay nuevo aprendizaje
```

**No existe "DONE" parcial.** Si una capa está sin implementar: no es DONE.

---

## Prioridad de ejecución

```
Ejecutar en orden:
FASE 0 → FASE 1 + FASE 2 (paralelas) → FASE 3 → FASE 4
                 ↑
    FASE 5 y FASE 6 crecen en paralelo con cada fase

Después de Fase 4:
FASE 7 → FASE 8 → FASE 9 → FASE 10 → FASE 11 → FASE 12
```

---

## Primer paso aprobado

Confirmar con Fidel:
1. ¿Los 3 gaps de Contabilidad (Fase 4) deben resolverse ahora o en la Fase 4?
2. ¿Iniciamos con Fase 0 ahora?
