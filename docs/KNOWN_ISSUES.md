# DFC Talento Humano — Problemas conocidos y deuda técnica

> Última actualización: 2026-10-22

---

## KI-001 — Sin límite máximo de rango de fechas en `GET /api/attendance-stats`

| Campo | Detalle |
|---|---|
| **Archivo** | `backend/app/Http/Controllers/Api/AttendanceStatsController.php:26-27` |
| **Detectado** | 2026-10-20 — CENTINELA, auditoría SPEC-017 |
| **Severidad** | LOW |
| **Estado** | **RESUELTO** — 2026-10-20. Límite de 366 días definido e implementado (Fidel, SPEC-017 ajustes finales). |

### Descripción

El endpoint `GET /api/attendance-stats` acepta cualquier rango de fechas entre `from` y `to` sin límite máximo. Solo se valida que `to >= from`. Un rango muy amplio (p. ej. varios años) produce un `SELECT` con JOINs sobre toda la tabla `attendances` para la empresa, lo que puede ser costoso en instancias con historial extenso.

### Impacto

- **Rendimiento:** para empresas con alta rotación y muchos registros de asistencia, un rango de 3–5+ años podría tardar varios segundos y consumir memoria significativa en el proceso PHP.
- **DoS interno:** un usuario autenticado con `attendance.manage` puede lanzar repetidamente consultas de rango amplio, degradando el servidor para otros usuarios de la misma instancia.
- **No es explotable externamente** sin autenticación + permiso.

### Mitigación existente

- El endpoint requiere `auth:sanctum` + `can:attendance.manage` — no es accesible sin credenciales válidas.
- El índice `(company_id, date, status)` en `attendances` cubre la consulta; el impacto real depende del volumen de datos.

### Resolución aplicada (2026-10-20)

Fidel definió el límite en **366 días** (cubre un año completo incluyendo años bisiestos). Implementado como closure validator en `AttendanceStatsController::__invoke()`:

```php
function ($attribute, $value, $fail) use ($request) {
    $from = $request->input('from');
    if (! $from || ! strtotime($from)) { return; }
    $maxTo = \Carbon\Carbon::parse($from)->addDays(365)->toDateString();
    if ($value > $maxTo) {
        $fail('El rango máximo permitido es de 366 días.');
    }
},
```

Tests: `range_of_366_days_is_accepted` y `range_of_367_days_returns_422` — ambos pasan.

### Criterio de cierre — CUMPLIDO

(1) Fidel definió 366 días. (2) Validación implementada y probada. (3) Este documento actualizado.

---

---

## KI-002 — RoleController sin guard de empresa (roles globales modificables por cualquier admin)

| Campo | Detalle |
|---|---|
| **Archivos** | `backend/app/Http/Controllers/Api/RoleController.php`, `backend/app/Http/Controllers/Api/UserController.php`, `backend/database/migrations/2026_10_22_200000_add_company_id_to_roles_table.php` |
| **Detectado** | 2026-10-22 — CENTINELA, hallazgo H2 |
| **Severidad** | ALTO |
| **Estado** | **RESUELTO** — 2026-10-22. Opción B implementada: roles ahora son per-company. |

### Descripción

Los roles del sistema eran globales (sin `company_id` en la tabla `roles`). `RoleController::update()` usaba `Role::findOrFail($id)` sin filtrar por empresa. Un administrador de empresa A con `roles.manage` podía renombrar o desactivar roles que afectan a otras empresas.

### Resolución aplicada (2026-10-22)

Se implementó **Opción B** (per-company roles):

- Migración `2026_10_22_200000_add_company_id_to_roles_table.php`: añade `company_id` nullable con FK a `companies`; reemplaza el unique `(name, guard_name)` de Spatie por `(company_id, name, guard_name)`.
- `RoleController`: todos los métodos filtran por `company_id` del token. `store()` usa asignación directa (no `Role::create()`) para evadir el check global de unicidad de Spatie, que no conoce `company_id`.
- `UserController::store()` y `update()`: validan que el rol asignado pertenezca a la misma empresa antes de `syncRoles()`.
- Seeders (`DatabaseSeeder`, `DemoSeeder`) actualizados para incluir `company_id` en la creación de roles.
- Tests de regresión: 4 tests en `RoleControllerTest` + 1 en `UserControllerTest`. 441/441 pasan.

### Criterio de cierre — CUMPLIDO

(1) Migración aplicada. (2) Aislamiento multiempresa en todos los métodos. (3) Asignación de roles valida ownership. (4) Suite completa verde.

---

## KI-003 — LeadController sin ownership check (por diseño — documentar explícitamente)

| Campo | Detalle |
|---|---|
| **Archivo** | `backend/app/Http/Controllers/Api/LeadController.php` |
| **Detectado** | 2026-10-22 — CENTINELA, hallazgo H3 |
| **Severidad** | MEDIO (contenido por diseño) |
| **Estado** | **DOCUMENTADO** — comportamiento intencional con restricción de permiso |

### Descripción

El modelo `Lead` no tiene `company_id` — los leads son prospectos del SaaS propietario, no datos de empresas cliente. `LeadController::update()` no verifica ownership porque cualquier operador del sistema propietario puede gestionar cualquier lead.

### Restricción obligatoria

El permiso `leads.*` NUNCA debe asignarse a usuarios de empresas cliente. Reservar exclusivamente para usuarios internos de la plataforma DFC.

---

---

## KI-004 — 26 errores ESLint preexistentes (`react-hooks/set-state-in-effect`)

| Campo | Detalle |
|---|---|
| **Archivos** | `src/lib/use-api-table.ts:25`, `src/components/crud/crud-modal.tsx:102,107,194`, y ~12 páginas CRUD |
| **Detectado** | 2026-10-22 — suite de verificación completa |
| **Severidad** | LOW (deuda técnica — no bloquea build ni producción) |
| **Estado** | **RESUELTO** — 2026-10-22. Commit `05491b3`. 0 errores tras refactorizar 22 archivos. |

### Descripción

`npm run lint` reporta 26 errores de la regla `react-hooks/set-state-in-effect`. Patrón recurrente:

```typescript
// Patrón que dispara la regla (simplificado):
useEffect(() => {
  setState(value);  // setState llamado directamente dentro de useEffect
}, [dep]);
```

La regla prohíbe actualizar estado directamente en un efecto sin condición de limpieza, ya que puede causar render loops o comportamientos inesperados en StrictMode.

### Impacto real

- `npm run build` (producción) **ignora los errores de lint** — el build es limpio (verificado 2026-10-22).
- `npm run dev` funciona correctamente.
- Las páginas afectadas funcionan en runtime porque React 18 StrictMode no dispara los casos degenerados para estos patrones específicos.

### Deuda técnica

Corregir requiere refactorizar los efectos para usar patrones como `useCallback` + dependencias correctas, o restructurar el flujo de estado. Es trabajo no trivial que puede introducir regresiones si no se hace con cuidado. Pospuesto a fase de refactoring dedicada.

### Criterio de cierre

(1) Refactorizar todos los efectos afectados con revisión de NANCY. (2) `npm run lint` pasa con 0 errores. (3) Regresión en E2E: 12/12.

---

*Añadir nuevos ítems con prefijo `KI-NNN` en orden cronológico.*
