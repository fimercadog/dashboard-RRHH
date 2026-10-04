# VERTICALS.md — Verticales del ERP

## Verticales implementadas

| Vertical | Fase | Estado |
|----------|------|--------|
| RRHH | 0 | ✅ Completo |
| CRM | A | ✅ Completo |
| Inventario | B | ✅ Completo |
| Compras | C | 🚧 En progreso (modelos/BD listo, falta controller/API/frontend) |
| Ventas | D | ❌ No iniciado |
| Finanzas | E | ❌ No iniciado |
| Contabilidad | F | ❌ No iniciado |

---

## Matriz de cobertura por vertical

```
                    RRHH    CRM     INV     COMPRAS  VENTAS  FINANZAS  CONTAB
Backend              ✅      ✅      ✅       🚧       ❌       ❌        ❌
Database             ✅      ✅      ✅       🚧       ❌       ❌        ❌
API                  ✅      ✅      ✅       ❌       ❌       ❌        ❌
Permisos (seed)      ✅      ✅      ✅       🚧       ❌       ❌        ❌
Auth guard           ✅      ✅      ✅       -        -        -         -
Frontend             ✅      ✅      ✅       ❌       ❌       ❌        ❌
Responsive/móvil     ✅      ✅      ✅       ❌       ❌       ❌        ❌
Export CSV/PDF       ✅      ✅      ✅       ❌       ❌       ❌        ❌
Contingencia         ✅*     ✅*     ✅*      ❌       ❌       ❌        ❌
Tests                ❌      ❌      ❌       ❌       ❌       ❌        ❌
E2E                  ❌      ❌      ❌       ❌       ❌       ❌        ❌

* ModuleTablePage ya soporta contingencia; los módulos están registrados pero
  no todos están explícitamente habilitados en la config de contingencia.
```

---

## Vertical: RRHH (Fase 0)

**Funcionalidades del core reutilizadas:** BaseCrudController, TableQueryService, AuditService, ModuleTablePage, DataTable, CrudModal, ResolvesCompany, Auth

**Específicas de RRHH:**
- Modelo Employee separado de User (employee_id nullable en users)
- Flujo solicitud/aprobación: VacationRequest, PermissionRequest, SickLeave
- ShiftAssignment (join de Shift ↔ Employee)
- EmployeeDocument por empleado

**Pendientes conocidos:**
- Perfil de empleado: tabs no todos conectados a API
- Policies/gates finos por recurso (sólo permisos Spatie, no policies Laravel)
- Tests y E2E

---

## Vertical: CRM (Fase A)

**Funcionalidades del core reutilizadas:** BaseCrudController, TableQueryService, AuditService, ModuleTablePage, DataTable, CrudModal

**Específicas de CRM:**
- Entidades: Lead, Client (con company_name), ClientNote, Contact, Deal, Activity, Segment
- Deal tiene pipeline con stages
- Activity vinculada a Deal y/o Client

**Pendientes conocidos:**
- Kanban visual para Deal pipeline
- Tests y E2E

---

## Vertical: Inventario (Fase B)

**Funcionalidades del core reutilizadas:** BaseCrudController, TableQueryService, AuditService, ModuleTablePage, DataTable, CrudModal

**Específicas de Inventario:**
- `StockService` — único escritor de stock_movements y product_stock
- `ProductStock` — stock actual por product + warehouse
- `StockMovement` — historial con avg_cost, reference_type, reference_id
- `InvalidProductTypeException` — lanzada al intentar mover stock de tipo service

**Integración hacia Compras/Ventas:**
- `stock_movements.reference_type` acepta: `manual_entry/exit`, `purchase_order`, `sale_order`, etc.
- `stock_movements.journal_entry_id` + `posted_at` — ganchos para Fase F (null por ahora)
- `products.inventory_account_code/cogs_account_code/sale_account_code` — ganchos para Fase F

**Pendientes conocidos:**
- Tests y E2E

---

## Vertical: Compras (Fase C) — EN PROGRESO

**Diseño de integración:** `docs/arquitectura-fases-c-f.md` — LEER antes de implementar.

**Modelos creados (sin commit):** Supplier, PurchaseOrder, PurchaseOrderItem, PurchaseReceipt
**Migraciones creadas (sin commit):** purchases_tables, purchases_permissions
**Pendiente:**
- PurchaseInvoice model + migration
- accounts_payable migration
- PurchaseService (llama a StockService::entry con reference_type='purchase_receipt')
- Controllers: SupplierController, PurchaseOrderController, PurchaseReceiptController, PurchaseInvoiceController
- Resources JSON
- API routes en api.php
- Frontend: /app/compras/{proveedores, ordenes, recepciones, facturas}
- Rutas en nav del shell

---

## Regla para construir una nueva vertical

Antes de escribir código, responder:
1. ¿Qué tablas necesita y cómo se relacionan con las existentes?
2. ¿Necesita un Service propio, o alcanza con BaseCrudController?
3. ¿Cómo se integra con StockService, si aplica?
4. ¿Qué permisos necesita? → migración de permisos primero.
5. ¿Qué componentes frontend puede reutilizar?
6. ¿Afecta algún módulo ya funcionando?

Después de implementar, verificar la matriz de cobertura para esa vertical antes de declarar DONE.
