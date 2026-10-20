# DFC Talento Humano — Estado del proyecto

> Última verificación: 2026-10-20
> Rama: `master` · HEAD: `81758b2`
> Cambios en curso (unstaged + untracked): ficha ampliada del empleado (ver sección 2)

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
| **Indicadores (ausencias, tardanzas, horas extra)** | ⛔ Brecha — no implementado |

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

## 2. Cambios en curso (no commiteados)

Verificados y aprobados por CENTINELA + NANCY el 2026-10-20.
**No hacer commit ni push sin autorización explícita.**

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
| **SST** | ⛔ No implementado | Media |
| **Desempeño** | ⛔ No implementado | Media |
| **Central documental** | ⛔ No implementado | Media |
| Indicadores de asistencia | ⛔ No implementado | Media |
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
| Backend (`php artisan test`) | **379** | **946** | 2026-10-20 | SQLite local |
| E2E Playwright | 11 | — | sesión anterior | No re-ejecutados — no declarar como estado actual |

---

## 7. Auditorías de seguridad

| Fecha | Alcance | Resultado |
|---|---|---|
| 2026-10-20 | `EmployeeWorkHistoryController` + SS + frontend historial | APROBADO (CENTINELA) |
| Sesión anterior | MEDIUM-1, CA-17, LOW-3 | Resueltos — commit `6bf010c` |

---

## 8. Próximo paso recomendado

1. **Commit** de los cambios en curso (requiere autorización).
2. **Indicadores de asistencia** — brecha de menor alcance.
3. **Nómina** — mayor impacto; requiere SPEC-PLANNER antes de implementar.

---

## 9. Fuera de alcance definido

Portal de autoservicio, facturación de outsourcing, clientes de outsourcing, reportes PILA.
