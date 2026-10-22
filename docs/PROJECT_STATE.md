# DFC Talento Humano — Estado del proyecto

> Última verificación: 2026-10-22
> Rama: `master` · HEAD: `3539ed7` · remoto sincronizado (origin/master)
> Árbol limpio — no hay cambios sin commit

---

## 1. Núcleo de Recursos Humanos

### Empleados

| Funcionalidad | Implementado | Probado (backend) | Desplegado |
|---|:---:|:---:|:---:|
| CRUD básico (crear, editar, desactivar) | ✓ | ✓ | ✓ |
| Búsqueda, filtros, paginación | ✓ | ✓ | ✓ |
| Exportación CSV / PDF | ✓ | ✓ | ✓ |
| Selector optimizado (grande volumen) | ✓ | ✓ `EmployeeSelectorPerformanceTest` | ✓ |
| Ficha: datos personales, laborales, contacto emergencia | ✓ | ✓ | ✓ |
| Estado `pending_withdrawal` | ✓ | ✓ `StoreEmployeeRequest` | ⚠ pendiente commit |
| **Seguridad social (EPS, ARL, pensión, caja)** | ✓ | ✓ `EmployeeWorkHistoryTest` | ⚠ pendiente commit |
| **Documentos asociados al empleado** | ✓ | ✓ `HrModuleRbacTest` | ✓ |
| **Historial laboral (visualización + escritura CRUD)** | ✓ | ✓ `EmployeeWorkHistoryTest` | ⚠ pendiente commit |

### Organización

| Funcionalidad | Estado |
|---|---|
| Departamentos | Implementado y desplegado |
| Cargos / posiciones | Implementado y desplegado |

### Asistencia

| Funcionalidad | Estado |
|---|---|
| Registro de entradas / salidas (check-in / check-out) | Implementado y desplegado |
| Registro de tardanzas | Implementado y desplegado |
| Consulta de registros | Implementado y desplegado |
| **Indicadores de asistencia (ausencias, tardanzas)** | ✓ Implementado — SPEC-017, pendiente commit |
| **Horas extra** | ⛔ Brecha de modelo — ver KI-001 y SPEC-017 §Brechas |

### Turnos

| Funcionalidad | Estado |
|---|---|
| Gestión de turnos y horarios | Implementado y desplegado |

### Vacaciones

| Funcionalidad | Estado |
|---|---|
| Solicitudes, consulta, flujo de aprobación | Implementado y desplegado |

### Incapacidades

| Funcionalidad | Estado |
|---|---|
| Registro, asociación al empleado | Implementado y desplegado |
| **Integración con nómina** | ⛔ Brecha — depende de módulo Nómina |

### Permisos

| Funcionalidad | Estado |
|---|---|
| Solicitudes, consulta, gestión | Implementado y desplegado |

### Reclutamiento

| Funcionalidad | Estado |
|---|---|
| Vacantes, candidatos, postulaciones, entrevistas | Implementado y desplegado |
| **Indicadores (vacantes abiertas, candidatos, contrataciones)** | ⛔ Brecha — no implementado |

---

## 2. Cambios recientes commiteados (sesión 2026-10-22)

Todos en master, empujados a origin. Árbol limpio.

| Commit | Descripción |
|---|---|
| `9212358` | fix(security): H-UC1/UC2 — IDOR employee_id + avatar_path en UserController |
| `6a4f4eb` | test(security): H2 — 3 tests adicionales de roles por empresa |
| `6edc521` | fix(security): H2 — roles per-company (migración + RoleController + seeders + tests) |
| `64573eb` | chore(ci+docs): GitHub Actions CI + KNOWN_ISSUES H2/H3 |
| `f5b3281` | fix(security): IDOR crítico UserController + migración PHPUnit #[Test] |
| `5494fa1` | test(e2e): fix selector Editar vs "Editar foto" en Playwright |

**Módulos implementados (no en lista anterior):**
- SST (incidentes) — `SstIncidentController` + 12 tests
- Desempeño (evaluaciones) — `PerformanceReviewController` + tests
- KPI cards en 48 páginas — 27 endpoints `stats()` backend
- Indicadores de asistencia SPEC-017 — `AttendanceStatsController` + 15 tests

### SPEC-017 — Indicadores de asistencia (2026-10-20)

Auditado por CENTINELA (3 × LOW — LOW-1 y LOW-3 resueltos, LOW-2 → KI-001 resuelto).
Aprobado por NANCY + MARGARITA. 396 tests / 996 assertions. TypeScript limpio.
Ajustes finales aplicados: navegación `router.push('/app/asistencia')`, validación 366 días, KI-001 cerrado.

| Archivo | Cambio |
|---|---|
| `backend/app/Http/Controllers/Api/AttendanceStatsController.php` | Nuevo — endpoint `GET /api/attendance-stats` |
| `backend/routes/api.php` | +1 ruta con `can:attendance.manage` |
| `backend/tests/Feature/AttendanceStatsTest.php` | Nuevo — 15 tests |
| `frontend/src/lib/types.ts` | +3 tipos: `AttendanceStats`, `AttendanceStatsByEmployee`, `AttendanceStatsByDepartment` |
| `frontend/src/app/app/asistencia/estadisticas/page.tsx` | Nueva página de indicadores |
| `docs/KNOWN_ISSUES.md` | Nuevo — KI-001 (límite de rango de fechas) |

### Ficha ampliada del empleado (ya en commit c848554)

### Archivos modificados (tracked)

| Archivo | Cambio |
|---|---|
| `backend/app/Http/Controllers/Api/EmployeeController.php` | `show` con eager load de `workHistory` y `documents` |
| `backend/app/Http/Requests/StoreEmployeeRequest.php` | Validación campos SS + status `pending_withdrawal` |
| `backend/app/Http/Resources/EmployeeResource.php` | Campos SS + `work_history` embebido |
| `backend/app/Models/Employee.php` | Fillable SS + relación `workHistory` |
| `backend/routes/api.php` | 4 rutas `/employees/{id}/work-history` con `can:employees.manage` |
| `frontend/src/app/app/empleados/[id]/employee-profile-client.tsx` | Tabs ficha/historial/documentos; UI CRUD historial |
| `frontend/src/app/app/empleados/page.tsx` | Campos SS en formulario |
| `frontend/src/lib/types.ts` | Tipo `EmployeeWorkHistory`; campos SS en `Employee` |
| `frontend/src/components/ui/badge.tsx` | Variante `pending_withdrawal` |
| `frontend/src/components/module-table-page.tsx` | Ajuste menor |

### Archivos nuevos (untracked)

| Archivo | Descripción |
|---|---|
| `backend/app/Http/Controllers/Api/EmployeeWorkHistoryController.php` | CRUD con doble validación `company_id` + `employee_id` |
| `backend/app/Http/Resources/EmployeeWorkHistoryResource.php` | Recurso JSON |
| `backend/app/Models/EmployeeWorkHistory.php` | Modelo, fillable, casts, relación |
| `backend/database/migrations/2026_10_20_100001_add_social_security_to_employees_table.php` | Columnas EPS, ARL, pension_fund, compensation_fund |
| `backend/database/migrations/2026_10_20_100002_create_employee_work_history_table.php` | Tabla con índice `(company_id, employee_id)` |
| `backend/tests/Feature/EmployeeWorkHistoryTest.php` | 11 tests: CRUD, permisos, aislamiento, SS |

### Estado de migraciones (2026-10-20)

Ambas migraciones corrieron en el entorno local (`php artisan migrate:status` confirmado).

---

## 3. Módulos por construir (brechas confirmadas)

| Módulo | Estado | Prioridad |
|---|---|---|
| **Nómina y liquidaciones** | ⛔ Solo especificación | Alta |
| **SST** | ✓ Implementado (2026-10-21) | Media |
| **Desempeño** | ✓ Implementado (2026-10-21) | Media |
| **Central documental** | ⛔ No implementado | Media |
| Horas extra (indicador asistencia) | ⛔ Brecha de modelo — KI-001 | Media |
| Indicadores de reclutamiento | ⛔ No implementado | Baja |

---

## 4. Módulos comerciales / ERP heredados

Presentes en la vertical. Profundidad no auditada en esta sesión.

- Clientes, Leads, CRM, Ventas, Compras, Inventario, Finanzas, Contabilidad

---

## 5. Integraciones

| Integración | Estado |
|---|---|
| WhatsApp | Implementado — operación en producción por verificar |
| Google Sheets | Implementado — flujos de sincronización por verificar |
| Zoom | Implementado — flujos completos por verificar |
| IA (OpenAI, Anthropic) | Configuración implementada — casos de uso por módulo sin validar |

---

## 6. Resultados de pruebas

| Suite | Tests | Assertions | Fecha | Entorno |
|---|---|---|---|---|
| Backend SQLite (`php artisan test`) | **447** | **1116** | 2026-10-22 | SQLite en memoria |
| Backend MySQL (`phpunit.mysql.xml`) | **439** | **1088** | 2026-10-22 | MariaDB local |
| E2E Playwright | **12** | — | 2026-10-22 | Chromium bundled |
| TypeScript (`tsc --noEmit`) | 0 errores | — | 2026-10-22 | — |
| Build producción (`npm run build`) | ✅ | — | 2026-10-22 | — |
| Lint ESLint | ⚠ 26 errores preexistentes | — | 2026-10-22 | KI-004 |

---

## 7. Auditorías de seguridad

| Fecha | Alcance | Resultado |
|---|---|---|
| 2026-10-20 | `AttendanceStatsController` + ruta + tests + frontend | APROBADO (CENTINELA) — LOW-1/LOW-3 resueltos, LOW-2 → KI-001 |
| 2026-10-20 | `EmployeeWorkHistoryController` + SS + frontend historial | APROBADO (CENTINELA) |
| Sesión anterior | MEDIUM-1, CA-17, LOW-3 | Resueltos — commit `6bf010c` |

---

## 8. Próximo paso recomendado

1. **Commit** de SPEC-017 (indicadores de asistencia) — requiere autorización.
2. **KI-001** — definir con Fidel el rango máximo de fechas antes de implementar la restricción.
3. **Nómina** — mayor impacto; requiere SPEC-PLANNER antes de implementar.

---

## 9. Fuera de alcance definido

Portal de autoservicio, facturación de outsourcing, clientes de outsourcing, reportes PILA.
