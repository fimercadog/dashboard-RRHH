# Diagnóstico de Rendimiento — FidelOS ERP
**Fecha:** 2026-10-13  
**Escenario de referencia:** 100.000 registros por tabla operativa  
**Método:** Verificación directa en código y migraciones — ningún hallazgo se presenta sin evidencia

---

## Resumen ejecutivo

| Categoría | Confirmados | Falsos positivos | Hipótesis pendientes |
|-----------|-------------|-----------------|----------------------|
| Críticos  | 1           | 0               | 0                    |
| Altos     | 3           | 1 (revertido)   | 0                    |
| Medios    | 4           | 0               | 2                    |
| Opcionales| 1           | 0               | 0                    |

---

## ❌ REVERTIDO — Hallazgo A3 (falso positivo)

**Diagnóstico anterior:** `attendances` sin índice compuesto `(company_id, date, status)`.  
**Verificación:** `backend/database/migrations/2026_08_24_044400_create_attendances_table.php` línea 23:

```php
$table->index(['company_id', 'date', 'status']);
```

El índice **existe desde la migración original**. No hay defecto aquí.

---

## 🔴 CRÍTICO — C1: Selector de empleados sin límite

**Archivo:** `backend/app/Http/Controllers/Api/EmployeeController.php`

```php
public function selector(Request $request): JsonResponse
{
    $employees = Employee::where('company_id', $this->companyId($request))
        ->where('employment_status', 'active')
        ->orderBy('first_name')
        ->get(['id', 'first_name', 'last_name']); // ← SIN LIMIT
    return response()->json($employees->map(...));
}
```

**Problema:** Con 100k empleados activos, `->get()` carga toda la colección en memoria del servidor y envía todo el JSON al cliente. El componente `RelationSelectField` en `frontend/src/components/crud/crud-modal.tsx` renderiza todos los resultados en un `<select>` nativo sin paginación ni búsqueda.

**Páginas afectadas (6):** asistencia, documentos, incapacidades, vacaciones, usuarios, permisos.

**Corrección mínima propuesta:**
- Backend: convertir `selector()` a endpoint con `?search=` y `limit=30` (búsqueda server-side).
- Frontend: reemplazar `<select>` estático por campo con debounce + búsqueda contra el endpoint.

---

## 🟠 ALTO — A1: Índices faltantes en tablas de alta escritura

### A1a: `audit_logs` sin índice `(company_id, created_at)`

**Archivo:** `backend/database/migrations/2026_08_24_044403_create_audit_logs_table.php`

```php
$table->index(['company_id', 'entity', 'action']); // existe
// NO existe (company_id, created_at)
```

`AuditLogController` ordena por `created_at DESC` (heredado de `BaseCrudController`). Sin índice, MySQL hace filesort sobre la partición de la empresa. A 500k+ filas de audit_log (10 acciones/min × 1 año) esto degradará el listado de auditoría.

### A1b: `stock_movements` sin índice `(company_id, created_at)`

**Archivo:** `backend/database/migrations/2026_10_04_000002_create_inventory_tables.php`

```php
$table->index(['product_id', 'warehouse_id']); // existe
$table->index(['reference_type', 'reference_id']); // existe
// NO existe (company_id, created_at)
```

`StockMovementController` usa `->latest()` (= `ORDER BY created_at DESC`). El mismo problema de filesort.

**Corrección mínima:**

```php
// Nueva migración
Schema::table('audit_logs', function (Blueprint $table) {
    $table->index(['company_id', 'created_at']);
});
Schema::table('stock_movements', function (Blueprint $table) {
    $table->index(['company_id', 'created_at']);
});
```

---

## 🟠 ALTO — A2: Tablas comerciales sin índices de filtrado/ordenación

**Archivos verificados:**
- `backend/database/migrations/2026_10_05_000002_create_purchases_tables.php` — `purchase_orders` solo tiene `unique(['company_id', 'number'])`
- `backend/database/migrations/2026_10_06_000002_create_sales_tables.php` — `sale_orders` y `quotes` igual

Estas tablas carecen de `(company_id, status)` y `(company_id, created_at)`. Los filtros de estado (`pendiente/aprobado`) y la paginación por fecha son los casos de uso más frecuentes en los listados de compras/ventas.

**Corrección mínima:** una migración con los 6 índices faltantes (3 tablas × 2 índices).

---

## 🟠 ALTO — A3 NEW: `DealController` carga modelo `Client` completo

**Archivo:** `backend/app/Http/Controllers/Api/DealController.php`

```php
protected array $with = ['client', 'owner:id,name']; // 'client' sin restricción de columnas
```

Todos los campos del cliente (teléfono, dirección, notas, etc.) se cargan para cada deal en el índice. Contrasta con la práctica correcta del sistema: `'client:id,company_name,first_name,last_name'` en `SaleOrderController`.

**Corrección mínima:**
```php
protected array $with = ['client:id,company_name,first_name,last_name', 'owner:id,name'];
```

---

## 🟡 MEDIO — M1: Índices faltantes en `deals`

**Archivo:** `backend/database/migrations/2026_10_02_000004_create_deals_table.php`

Solo FK implícita en `company_id`, sin índices compuestos para los filtros habituales de CRM (por etapa, por owner, por fecha).

**Corrección mínima:**
```php
$table->index(['company_id', 'stage']);
$table->index(['company_id', 'owner_id']);
$table->index(['company_id', 'created_at']);
```

---

## 🟡 MEDIO — M2: `AuditLogController` carga modelo `User` completo

**Archivo:** `backend/app/Http/Controllers/Api/AuditLogController.php`

```php
protected array $with = ['user']; // sin restricción de columnas
```

En un listado de auditoría paginado a 10 filas, esto carga los datos completos de usuario para cada entrada. Corrección mínima: `['user:id,name,email']`.

---

## 🟡 MEDIO — M3: Orders/Quotes/Purchases cargan `items.product` en índice (hipótesis)

**Archivos:** `SaleOrderController`, `QuoteController`, `PurchaseOrderController`

```php
protected array $with = ['client:...', 'warehouse:...', 'items.product:id,sku,name'];
```

Correcto en columnas (column-restricted), no es N+1 (eager load), pero para pedidos con muchos ítems puede generar respuestas grandes. Con paginación fija a 10 registros esto es aceptable hoy. **Hipótesis:** degradará cuando los pedidos promedio superen 20-30 líneas. No accionable ahora — monitorear.

---

## 🟡 MEDIO — M4: Dashboard con ~15 queries separadas (hipótesis)

**Archivo:** `backend/app/Http/Controllers/Api/DashboardController.php`

Ejecuta ~15 COUNTs independientes incluyendo tres conteos de asistencia de hoy. Tiene `->limit()` en todas las listas (10/5/8). Cada query tiene su índice correspondiente (confirmado: `attendances` ya tiene el índice compuesto correcto). **El problema no es la ausencia de índices sino la cantidad de round-trips.** Impacto medible solo en producción con alta concurrencia. Opcional consolidar los tres COUNTs de asistencia en uno solo.

---

## 🔵 OPCIONAL — O1: LIKE con wildcard inicial

**Archivo:** `backend/app/Services/TableQueryService.php` línea 17

```php
// ponytail: LIKE con leading wildcard, migrar a FULLTEXT cuando search sea lento en empresas >500k registros/tabla
$builder->orWhere($field, 'like', "%{$search}%");
```

Ya documentado en el código como deuda conocida con ceiling explícito. No accionable antes de confirmar degradación real con 500k+ filas. No requiere FULLTEXT ahora.

---

## Resumen de cambios mínimos propuestos

### Migración A — Índices críticos (sin riesgo de regresión)
```
audit_logs:      (company_id, created_at)
stock_movements: (company_id, created_at)
purchase_orders: (company_id, status), (company_id, created_at)
sale_orders:     (company_id, status), (company_id, created_at)
quotes:          (company_id, status), (company_id, created_at)
deals:           (company_id, stage), (company_id, owner_id), (company_id, created_at)
```
**11 índices en una migración. Sin tocar código PHP ni TypeScript. Rollback seguro.**

### Cambio de código B — Restricción de columnas en DealController
```php
protected array $with = ['client:id,company_name,first_name,last_name', 'owner:id,name'];
```
**1 línea. Sin efecto en tests existentes.**

### Cambio de código C — Restricción de columnas en AuditLogController
```php
protected array $with = ['user:id,name,email'];
```
**1 línea. Sin efecto en tests existentes.**

### Cambio mayor D — Selector de empleados (requiere SPEC separado)
El selector es el único cambio que toca tanto backend como frontend:
- Backend: nuevo endpoint con search+límite (o modificar `selector()` existente)
- Frontend: reemplazar `RelationSelectField` con combobox con debounce
- Las 6 páginas que usan `endpoint: "/employees/selector"` se actualizan automáticamente si el componente es retrocompatible

**Recomendación:** aprobar A+B+C primero (bajo riesgo, alto impacto). Luego SPEC separado para D.

---

## Plan de pruebas reproducibles

### Prerrequisito

```bash
# Extender BenchmarkSeedClients (ya existe) para empleados
php artisan benchmark:seed --employees=100000 --company=1
```

### Criterios de aceptación medibles

| Test | Endpoint | Criterio PASS | Herramienta |
|------|----------|---------------|-------------|
| Listado empleados paginado | GET /api/employees?page=1 | < 300ms p95 | curl + time |
| Selector empleados (antes fix) | GET /api/employees/selector | > 2000ms esperado | curl + time |
| Selector empleados (después fix) | GET /api/employees/selector?search=Juan | < 200ms | curl + time |
| Listado auditoría | GET /api/audit-logs?page=1 | < 300ms (con índice) | curl + time |
| Listado movimientos | GET /api/stock-movements?page=1 | < 300ms (con índice) | curl + time |
| Listado deals | GET /api/deals?page=1 | < 300ms | curl + time |
| Dashboard | GET /api/dashboard | < 500ms | curl + time |

### Regresión post-migración

```bash
php artisan test --filter=Performance
php artisan test --filter=HrModule
php artisan test --filter=Employee
php artisan test --filter=EmployeeRbac
```

---

## Lo que no se pudo verificar en esta pasada

1. **Tiempos reales de queries** — no se ejecutó EXPLAIN ANALYZE contra BD con 100k registros. Los criterios son estimaciones por selectividad.
2. **`StockController` filtro `low_stock`** — usa `whereHas + whereColumn + join`. Requiere EXPLAIN con datos reales.
3. **Concurrencia del Dashboard** — 15 queries × N usuarios. No medido. Hipótesis: aceptable hasta ~20 usuarios concurrentes por empresa.

---

*Diagnóstico producido por PEPE — verificación directa en código y migraciones. Sin implementación. Pendiente autorización.*
