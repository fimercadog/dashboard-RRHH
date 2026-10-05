# CORE_CONTRACT.md — Qué es el núcleo y qué no se toca
> Leer antes de cualquier modificación. Si una vertical necesita cambiar algo de esta lista,
> DECLARARLO EXPLÍCITAMENTE antes de hacerlo.

---

## 1. Backend — Contratos inamovibles

### `BaseCrudController` (`app/Http/Controllers/Api/BaseCrudController.php`)
Controlador base genérico. Toda vertical lo extiende declarando `$model`, `$resource`,
`$with`, `$searchable`, `$filterable`.

**Provee:** `index` (paginado), `store`, `show`, `update`, `destroy`.
**No tocar.** Si una vertical necesita lógica extra, sobreescribe el método, no modifica la base.
**FormRequest auto-detection:** si existe `App\Http\Requests\Store{Model}Request`, se usa en store/update.

### `ResolvesCompany` concern (`app/Http/Controllers/Api/Concerns/ResolvesCompany.php`)
Resuelve `company_id` del usuario autenticado. Todas las consultas deben scope con `company_id`.
**No tocar.** Es el contrato de multitenant.

### `TableQueryService` (`app/Services/TableQueryService.php`)
Aplica search/filter/sort/paginate a cualquier query. No debe modificarse por causa de una vertical.

### `AuditService` (`app/Services/AuditService.php`)
Registra acciones en `audit_logs`. Llamado automáticamente desde `BaseCrudController`.
Si un controller especial necesita auditar, usa `$audit->record(...)` directamente.

### `StockService` (`app/Services/StockService.php`)
**ÚNICO punto de escritura** a `stock_movements` y `product_stock`.
- Métodos: `entry`, `exit`, `adjustment`, `transfer`
- Mantiene `avg_cost` en `product_stock`
- Lanza `InvalidProductTypeException` para productos de tipo service
- **NUNCA** escribir `stock_movements` o `product_stock` directamente fuera de este servicio.
- Fases C–F se integran llamando a `StockService`, no duplicándolo.

---

## 2. Frontend — Contratos inamovibles

### `ModuleTablePage` (`src/components/module-table-page.tsx`)
Wrapper estándar para una página de módulo con tabla + CRUD. Todas las páginas de módulo
lo usan. Provee: título, descripción, botón crear, DataTable, CrudModal, soporte de contingencia.
**No modificar** por causa de un módulo específico. Si un módulo necesita layout especial,
crea su propio page.tsx sin usar este wrapper.

### `DataTable` (`src/components/data-table/data-table.tsx`)
Tabla reutilizable con TanStack Table: search, paginación backend, loading, error, vacío, export CSV/PDF.

### `CrudModal` / `crud-modal.tsx`
Modal de creación/edición genérico alimentado por `CrudField[]`.

### `useApiTable` hook
Hook que maneja state de tabla (data, search, page, loading, error, refresh) contra una API REST.

### `admin-shell.tsx` — Layout compartido del panel
`src/components/layout/admin-shell.tsx` es el único shell del panel privado.
Toda vertical lo usa. Contiene sidebar, header, guard de autorización y contingencia.

**Regla de ubicación del usuario autenticado:**
El bloque de identidad del usuario (nombre · ● Online · rol) pertenece al **sidebar**, no al header.
Orden en sidebar: logo+vertical → divisor → usuario → navegación.
El header contiene únicamente: BetaNotice, feedback, cambio de tema, logout.
**No mover el bloque de usuario al header.** No crear un segundo sidebar ni un segundo user-block.

### Auth guard
- `src/middleware.ts` protege `/app/*`
- `/auth/me` valida sesión activa
- La navegación se filtra por permisos del usuario autenticado

---

## 3. Base de datos — Reglas

### company_id en todo
Toda tabla de datos operativos tiene `company_id` (FK a `companies`). Sin excepción.
Las consultas siempre hacen scope por `company_id` del usuario autenticado.

### Tablas que no se modifican
| Tabla | Razón |
|-------|-------|
| `users` | Auth base; solo se extienden campos con migraciones cuidadosas |
| `roles`, `permissions`, tablas Spatie | Se gestionan vía seeders/migraciones de permisos, no manualmente |
| `audit_logs` | Se escribe solo vía AuditService |
| `stock_movements`, `product_stock` | Se escriben solo vía StockService |
| `personal_access_tokens` | Sanctum, no tocar |

### Ganchos de integración (ya en producción, no modificar estructura)
```
stock_movements.journal_entry_id   → null hasta Fase F
stock_movements.posted_at          → null hasta Fase F
products.inventory_account_code    → null hasta Fase F
products.cogs_account_code         → null hasta Fase F
products.sale_account_code         → null hasta Fase F
```

---

## 4. Autenticación y permisos

- **Auth:** Sanctum Bearer token. `/api/auth/login` → token. `/api/auth/me` → usuario+permisos.
- **Permisos:** Spatie `laravel-permission`. Los permisos del usuario vienen en la respuesta de `/auth/me` y el frontend filtra navegación con ellos.
- **Roles seed:** Super Admin · Administrador de empresa · Recursos Humanos · Supervisor · Empleado
- **Permisos por módulo:** ver `docs/roles-permissions.md`
- **Regla:** nuevas verticales DEBEN agregar sus permisos como migración, no como seeder ad hoc.

---

## 5. Patrones que toda vertical respeta

1. **Controller extiende `BaseCrudController`** — declarar `$model`, `$resource`, `$with`, `$searchable`, `$filterable`.
2. **Rutas en `routes/api.php`** — grupo `apiResource` o rutas manuales dentro del grupo Sanctum.
3. **Resource JSON** en `app/Http/Resources/`.
4. **FormRequest** para validación: `StoreXxxRequest` con reglas de store; update las hereda como `sometimes`.
5. **Frontend usa `ModuleTablePage`** salvo que el módulo necesite layout completamente distinto.
6. **Permisos en migración** — `add_{vertical}_permissions.php` con la lista exhaustiva.
7. **Auditoría automática** — BaseCrudController ya la hace; controllers especiales llaman `AuditService`.
8. **Contingencia** — ModuleTablePage ya maneja `moduleEnabled`; registrar el módulo en la config de contingencia si aplica.

---

## 6. CORE — Pendientes y Comunicaciones (reglas permanentes)

Los dos botones del header (`PendingButton`, `CommunicationsButton`) son servicios CORE del ERP.

### PendingService / PendingProvider
- `PendingService` vive en `app/Services/PendingService.php` y se registra como singleton en `AppServiceProvider`.
- Cada vertical que tenga ítems pendientes **registra su propio `PendingProvider`** en `AppServiceProvider::boot()`.
- **Una vertical NO duplica pendientes que ya pertenecen a otra vertical.** Regla de ownership:
  - CxP (AccountPayable) → PurchasesPendingProvider
  - CxC (AccountsReceivable) → SalesPendingProvider
  - Overdraft (CashAccount < 0) → FinancePendingProvider
- Cada provider llama internamente a `$user->can(...)` — el endpoint no lleva su propio gate de permisos.
- **NO** se crea una tabla `pending_actions`. Los ítems son cálculos en tiempo real.

### CampaignController / AudienceSource
- `CampaignController` recibe `AudienceSource[]` por constructor (DI en `AppServiceProvider::register()`).
- `GoogleSheetsSource::isReady()` retorna `false` sin `GOOGLE_SHEETS_API_KEY`. **Nunca mostrarla como lista.**
- El envío real de campañas está DIFERIDO — requiere proveedor de mensajería. Las campañas solo se guardan como `draft`.
- `communications.manage / communications.view` son los permisos — agregados en migración `2026_10_10_000001`.

---

## 7. Lo que NUNCA vuelve

- Módulos veterinarios/clínicos: Agenda, Citas, Pacientes, Servicios, Especies, Razas.
  → Fueron eliminados en limpieza 2026-10-03. Están en proyecto separado `demo-erp-web-veterinaria`.
