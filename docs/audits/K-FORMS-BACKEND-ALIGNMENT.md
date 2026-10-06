# K-FORMS — Backend Alignment Pass
**Fecha:** 2026-10-06
**Alcance:** Verificación de que el backend está preparado para los 24 fixes de formularios de K-FORMS
**Regla:** No se modificó ningún código durante esta auditoría — solo lectura y diagnóstico

---

## Executive Summary

El backend está **parcialmente listo**. Existen 2 issues P0 y 3 issues P1 que deben resolverse antes de iniciar los fixes de frontend.

Los más críticos:
1. **La infraestructura de upload de archivos no existe** — el `EmployeeDocumentController` solo maneja JSON. No hay endpoint multipart, no hay uso de `Storage`, no hay validación MIME. El fix F-001 (file_path → file upload) no puede implementarse en frontend sin esto.
2. **`StorePaymentRequest` y `StoreActivityRequest` no usan `ownedExists()`** — extienden `FormRequest` en lugar de `ApiFormRequest`, omitiendo el scope de company_id en las FKs. Riesgo de cross-company data access.

El resto del backend (rutas, modelos, relaciones, ownedExists) está correctamente implementado en la mayoría de módulos.

**BACKEND ALIGNMENT: FIX REQUIRED**

---

## 1. Checklist de rutas — todos los endpoints de selector

| Selector | Endpoint | Ruta registrada | Permiso requerido | Company scope | Estado |
|----------|----------|----------------|-------------------|---------------|--------|
| employee_id | GET /employees | ✓ (apiResource) | `employees.manage` | ✓ BaseCrudController | ⚠️ ver P1-003 |
| department_id | GET /departments | ✓ (apiResource) | `settings.manage` | ✓ | ⚠️ ver P1-003 |
| position_id | GET /positions | ✓ (apiResource) | `settings.manage` | ✓ | ⚠️ ver P1-003 |
| client_id | GET /clients | ✓ (apiResource) | `clients.manage` | ✓ | ✓ LISTO |
| deal_id | GET /deals | ✓ (apiResource) | `deals.manage` | ✓ | ✓ LISTO |
| category_id | GET /categories | ✓ (apiResource) | `inventory.manage` | ✓ | ✓ LISTO |
| brand_id | GET /brands | ✓ (apiResource) | `inventory.manage` | ✓ | ✓ LISTO |
| unit_id | GET /units | ✓ (apiResource) | `inventory.manage` | ✓ | ✓ LISTO |
| warehouse_id | GET /warehouses | ✓ (apiResource) | `inventory.manage` | ✓ | ✓ LISTO |
| payable_id (CxP) | GET /accounts-payable | ✓ (index, show) | `purchases.view` | ✓ | ✓ LISTO |
| payable_id (CxC) | GET /accounts-receivable | ✓ (index, show) | `sales.view` | ✓ | ✓ LISTO |
| cash_account_id | GET /cash-accounts | ✓ (apiResource) | `finance.manage` | ✓ | ✓ LISTO |
| parent_id (categorías) | GET /categories | ✓ | `inventory.manage` | ✓ | ✓ LISTO |
| parent_id (cuentas) | GET /chart-of-accounts | ✓ | `accounting.view` | ✓ | ✓ LISTO |

---

## 2. Alignment BD → Model → FormRequest → Resource

### 2.1 RRHH

| Modelo | FK | Tabla destino | Model relation | FormRequest | ownedExists() | Resource expone id+label |
|--------|----|----|----------------|-------------|--------------|--------------------------|
| EmployeeDocument | employee_id | employees | ✓ belongsTo | StoreEmployeeDocumentRequest | ✓ | EmployeeResource: id, full_name ✓ |
| EmployeeDocument | file_path | N/A — archivo | N/A | 'required', 'string' | N/A | ❌ **P0: string en vez de upload** |
| Employee | department_id | departments | ✓ belongsTo | StoreEmployeeRequest | ✓ ownedExists | DepartmentResource: id + name ✓ |
| Employee | position_id | positions | ✓ belongsTo | StoreEmployeeRequest | ✓ ownedExists | PositionResource: id + name ✓ |
| Attendance | employee_id | employees | ✓ belongsTo | StoreAttendanceRequest | ✓ ownedExists | ✓ |
| VacationRequest | employee_id | employees | ✓ belongsTo | StoreVacationRequestRequest | ✓ ownedExists | ✓ |
| PermissionRequest | employee_id | employees | ✓ belongsTo | StorePermissionRequestRequest | ✓ ownedExists | ✓ |
| SickLeave | employee_id | employees | ✓ belongsTo | StoreSickLeaveRequest | ✓ ownedExists | ✓ |
| Position | department_id | departments | ✓ belongsTo | StorePositionRequest | ✓ ownedExists | ✓ |

### 2.2 CRM

| Modelo | FK | Model relation | FormRequest | ownedExists() | Estado |
|--------|----|----|-------------|--------------|--------|
| Contact | client_id | ✓ belongsTo | StoreContactRequest | ✓ ownedExists | ✓ LISTO |
| Deal | client_id | ✓ belongsTo | StoreDealRequest | ✓ ownedExists | ✓ LISTO |
| Activity | deal_id | ✓ belongsTo | StoreActivityRequest | ❌ 'integer', 'min:1' | ❌ P1-001 |
| Activity | client_id | ✓ belongsTo | StoreActivityRequest | ❌ 'integer', 'min:1' | ❌ P1-001 |
| Activity | contact_id | ✓ belongsTo | StoreActivityRequest | ❌ 'integer', 'min:1' | ❌ P1-001 |

**StoreActivityRequest extiende `FormRequest` no `ApiFormRequest`** — no tiene acceso a `ownedExists()`.

### 2.3 Inventario

| Modelo | FK | FormRequest | ownedExists() | Estado |
|--------|----|----|-------------|--------|
| Product | category_id | StoreProductRequest | ✓ ownedExists | ✓ LISTO |
| Product | brand_id | StoreProductRequest | ✓ ownedExists | ✓ LISTO |
| Product | unit_id | StoreProductRequest | ✓ ownedExists | ✓ LISTO |
| Category | parent_id | StoreCategoryRequest | ✓ ownedExists | ✓ LISTO |

### 2.4 Finanzas

| Modelo | FK | FormRequest | ownedExists() | Estado |
|--------|----|----|-------------|--------|
| Payment | payable_id | StorePaymentRequest | ❌ 'integer', 'min:1' | ❌ P0-002 |
| Payment | cash_account_id | StorePaymentRequest | ❌ 'integer', 'min:1' | ❌ P0-002 |

**StorePaymentRequest extiende `FormRequest` no `ApiFormRequest`**.

### 2.5 Contabilidad

| Modelo | FK | FormRequest | ownedExists() | Estado |
|--------|----|----|-------------|--------|
| ChartOfAccount | parent_id | StoreChartOfAccountRequest | ❌ 'nullable', 'integer' | ❌ P1-002 |

**StoreChartOfAccountRequest extiende `FormRequest` no `ApiFormRequest`**.

---

## 3. Problemas encontrados

### P0-001 — Infraestructura de upload de archivos AUSENTE
**Archivo:** `EmployeeDocumentController.php`, `StoreEmployeeDocumentRequest.php`
**Endpoint:** POST /employee-documents
**Problema:** `file_path` se valida y almacena como string libre. `EmployeeDocumentController` extiende `BaseCrudController` que solo procesa JSON. No hay:
- Endpoint multipart/form-data
- Uso de `Storage::disk('public')->put()`
- Validación de MIME type / tamaño
- Ruta pública para servir el archivo

**Fix requerido:**
1. Agregar `upload()` en `EmployeeDocumentController` para multipart
2. Actualizar `StoreEmployeeDocumentRequest`: `'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240']`
3. Guardar con `Storage::disk('public')->put("documents/{$companyId}", $file)`
4. Ejecutar `php artisan storage:link` si no está hecho

**Clasificación:** CORE (patrón reutilizable para cualquier vertical con documentos)
**Estado:** FIX REQUIRED — bloquea fix de frontend

---

### P0-002 — StorePaymentRequest sin ownedExists() — riesgo cross-company
**Archivo:** `backend/app/Http/Requests/StorePaymentRequest.php`
**Problema:**
```php
class StorePaymentRequest extends FormRequest  // NO ApiFormRequest
'payable_id'      => ['required', 'integer', 'min:1'],   // sin company scope
'cash_account_id' => ['required', 'integer', 'min:1'],   // sin company scope
```
Un usuario puede enviar un `cash_account_id` de otra empresa y el validador lo acepta.

**Fix requerido:**
```php
class StorePaymentRequest extends ApiFormRequest
'cash_account_id' => ['required', $this->ownedExists('cash_accounts')],
// payable_id: validación condicional por payable_type (polymorphic)
```

**Clasificación:** CORE
**Estado:** FIX REQUIRED

---

### P1-001 — StoreActivityRequest sin ownedExists()
**Archivo:** `backend/app/Http/Requests/StoreActivityRequest.php`
**Problema:**
```php
class StoreActivityRequest extends FormRequest  // NO ApiFormRequest
'deal_id'    => ['nullable', 'integer', 'min:1'],   // sin company scope
'client_id'  => ['nullable', 'integer', 'min:1'],   // sin company scope
'contact_id' => ['nullable', 'integer', 'min:1'],   // sin company scope
```

**Fix requerido:**
```php
class StoreActivityRequest extends ApiFormRequest
'deal_id'    => ['nullable', 'integer', $this->ownedExists('deals')],
'client_id'  => ['nullable', 'integer', $this->ownedExists('clients')],
'contact_id' => ['nullable', 'integer', $this->ownedExists('contacts')],
```

**Clasificación:** CORE
**Estado:** FIX REQUIRED

---

### P1-002 — StoreChartOfAccountRequest sin ownedExists() para parent_id
**Archivo:** `backend/app/Http/Requests/StoreChartOfAccountRequest.php`
**Problema:**
```php
class StoreChartOfAccountRequest extends FormRequest  // NO ApiFormRequest
'parent_id' => ['nullable', 'integer'],  // sin company scope ni existencia
```

**Fix requerido:**
```php
class StoreChartOfAccountRequest extends ApiFormRequest
'parent_id' => ['nullable', 'integer', $this->ownedExists('chart_of_accounts')],
```

**Clasificación:** CORE
**Estado:** FIX REQUIRED

---

### P1-003 — Permiso cross-requirement en selectores de empleado
**Problema:**
Los formularios de Asistencia, Vacaciones, Permisos, Incapacidades y Documentos necesitan cargar empleados para el selector. `GET /employees` requiere `can:employees.manage`.

Un usuario con solo `attendance.manage`, `requests.approve` o `documents.manage` recibe **403** al cargar el dropdown.

| Módulo | Permiso del módulo | Permiso necesario para selector | Conflicto |
|--------|-------------------|--------------------------------|-----------|
| Asistencia | attendance.manage | employees.manage | ⚠️ posible 403 |
| Vacaciones | requests.approve | employees.manage | ⚠️ posible 403 |
| Permisos | requests.approve | employees.manage | ⚠️ posible 403 |
| Incapacidades | requests.approve | employees.manage | ⚠️ posible 403 |
| Documentos | documents.manage | employees.manage | ⚠️ posible 403 |
| Empleados form | employees.manage | settings.manage (depts/positions) | ⚠️ posible 403 |

**Fix requerido (opción A):** Agregar `GET /employees/selector` → solo `id + full_name`, accesible a cualquier usuario autenticado con `company_id`.

**Clasificación:** CORE (pattern de picker para todas las verticales)
**Estado:** FIX REQUIRED

---

### P2-001 — SickLeave.type sin enum en backend
**Archivo:** `backend/app/Http/Requests/StoreSickLeaveRequest.php`
**Problema:** `'type' => ['required', 'string', 'max:100']` — acepta cualquier string.
**Fix:** `'type' => ['required', Rule::in(['enfermedad_general','accidente_trabajo','licencia_maternidad','licencia_paternidad','otro'])]`
**Estado:** FIX REQUIRED

---

### P2-002 — EmployeeDocument.document_type sin enum
**Archivo:** `backend/app/Http/Requests/StoreEmployeeDocumentRequest.php`
**Problema:** `'document_type' => ['required', 'string', 'max:100']` — acepta cualquier string.
**Fix:** `'document_type' => ['required', Rule::in(['contrato','hoja_vida','diploma','certificado','soporte_disciplinario','otro'])]`
**Estado:** FIX REQUIRED

---

### P3-001 — Resources con parent::toArray() — respuesta no controlada
**Archivos:** DepartmentResource, PositionResource, DealResource, ClientResource
**Problema:** Exponen todos los campos del modelo incluyendo `company_id`, `deleted_at`, etc.
**Impacto:** Bajo — multitenancy garantizada por Bearer+ResolvesCompany, pero forma desordenada.
**Estado:** DEUDA TÉCNICA ACEPTADA (no bloquea frontend)

---

## 4. Matriz de Resources — suficiencia para selectores frontend

| Selector endpoint | id | label disponible | ¿Suficiente para selector? |
|---|---|---|---|
| GET /employees | ✓ | full_name (accessor) | ✓ SÍ |
| GET /departments | ✓ | name (via parent::toArray) | ✓ SÍ |
| GET /positions | ✓ | name (via parent::toArray) | ✓ SÍ |
| GET /clients | ✓ | first_name + last_name / company_name | ✓ SÍ |
| GET /deals | ✓ | title (via parent::toArray) | ✓ SÍ |
| GET /categories | ✓ | name | ✓ SÍ |
| GET /brands | ✓ | name | ✓ SÍ |
| GET /units | ✓ | name | ✓ SÍ |
| GET /warehouses | ✓ | name | ✓ SÍ |
| GET /accounts-payable | ✓ | amount + supplier.name | ✓ SÍ |
| GET /accounts-receivable | ✓ | amount + client.name | ✓ SÍ |
| GET /cash-accounts | ✓ | name | ✓ SÍ |
| GET /chart-of-accounts | ✓ | name + code | ✓ SÍ |

---

## 5. User ↔ Employee — arquitectura confirmada

```
User.employee_id (nullable) → FK a employees.id
User.$fillable incluye 'employee_id'
User::employee(): BelongsTo { return $this->belongsTo(Employee::class); }
UserController::$with = ['employee']
```

**STATUS: CONFIRMADO** — la relación existe, no requiere cambio de BD.

---

## 6. Audit de Storage

```
config/filesystems.php:
  disk 'public': storage_path('app/public'), visibility: public
  symlink: public_path('storage') → storage_path('app/public')

EmployeeDocumentController: extends BaseCrudController (solo JSON) ❌
StoreEmployeeDocumentRequest: 'file_path' => ['required', 'string'] ❌
```

**STATUS:** Disco `public` configurado ✓ — ningún controller lo usa para upload ❌

---

## 7. Resumen de fixes requeridos

| ID | Archivo(s) | Fix | Clasificación | Prioridad |
|----|-----------|-----|---------------|-----------|
| FIX-001 | EmployeeDocumentController + StoreEmployeeDocumentRequest | Endpoint multipart + Storage::disk('public') | CORE | P0 |
| FIX-002 | StorePaymentRequest | Cambiar a ApiFormRequest + ownedExists para cash_account_id | CORE | P0 |
| FIX-003 | StoreActivityRequest | Cambiar a ApiFormRequest + ownedExists para deal/client/contact | CORE | P1 |
| FIX-004 | StoreChartOfAccountRequest | Cambiar a ApiFormRequest + ownedExists para parent_id | CORE | P1 |
| FIX-005 | routes/api.php + EmployeeController | Endpoint GET /employees/selector sin permiso especial | CORE | P1 |
| FIX-006 | StoreSickLeaveRequest | Rule::in() para type | VERTICAL | P2 |
| FIX-007 | StoreEmployeeDocumentRequest | Rule::in() para document_type | VERTICAL | P2 |

---

## 8. Frontend readiness por módulo

| Formulario | Selector backend listo | Bloqueo para frontend |
|-----------|----------------------|----------------------|
| Documentos → employee_id | ✓ | — |
| Documentos → file | ❌ sin upload | **FIX-001** |
| Empleados → department_id / position_id | ✓ | ⚠️ FIX-005 (permisos) |
| Asistencia → employee_id | ✓ | ⚠️ FIX-005 (permisos) |
| Vacaciones → employee_id | ✓ | ⚠️ FIX-005 (permisos) |
| Permisos → employee_id | ✓ | ⚠️ FIX-005 (permisos) |
| Incapacidades → employee_id / type | ✓ | FIX-006 recomendado |
| CRM/Actividades → deal/client/contact | ✓ | — |
| Inventario/Productos → category/brand/unit | ✓ | — |
| Finanzas/Pagos → payable/cash_account | ✓ | ⚠️ FIX-002 antes |
| Contabilidad/Cuentas → parent_id | ✓ | ⚠️ FIX-004 antes |

---

## 9. Conclusión

**BACKEND ALIGNMENT: FIX REQUIRED**

**Bloqueos reales (P0):**
1. `FIX-001` — Sin endpoint de upload, el formulario de Documentos no puede tener file picker funcional
2. `FIX-002` — StorePaymentRequest sin ownedExists es vulnerabilidad de seguridad activa

**Secuencia recomendada antes de tocar frontend:**
1. FIX-001, FIX-002, FIX-003, FIX-004 — todos son cambios de 2-3 líneas en FormRequests + 1 nuevo método en EmployeeDocumentController
2. FIX-005 — nuevo endpoint selector (o ajuste de permisos)
3. FIX-006, FIX-007 — enums en validaciones

**Estimado:** ~2-3 horas para los 7 fixes. FIX-001 (upload) es el más extenso.
Después: iniciar los 24 fixes de formularios del frontend (K-FORMS Groups A–F).
