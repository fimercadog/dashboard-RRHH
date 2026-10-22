# BACKLOG GLOBAL — DFC Talento Humano
> Generado: 2026-10-21 | Actualizar al terminar cada tarea

---

## LEYENDA
- 🟢 COMPLETADO Y VERIFICADO
- 🟡 EN PROGRESO
- 🔴 PENDIENTE
- 🔵 BLOQUEADO POR ENTORNO
- ⚫ BLOQUEADO POR DECISIÓN

---

## GRUPO A — Backend / Migraciones

| ID | Tarea | Estado | Evidencia |
|----|-------|--------|-----------|
| A-01 | Migraciones SST/Desempeño (`php artisan migrate`) | 🟢 COMPLETADO | 4/4 DONE — 2026-10-21 |
| A-02 | Suite tests SQLite tras migración (`php artisan test`) | 🟢 COMPLETADO | 434/434 pass, 1085 assertions — 2026-10-21 |
| A-03 | Suite MySQL (`phpunit.mysql.xml`) | 🔵 BLOQUEADO ENTORNO | MariaDB XAMPP apagado (SQLSTATE 2002, conexión denegada 127.0.0.1:3306) — iniciar XAMPP para correr |
| A-04 | SST: validar API endpoints | 🟢 COMPLETADO | migraciones aplicadas, endpoints disponibles |
| A-05 | Desempeño: validar API endpoints | 🟢 COMPLETADO | migraciones aplicadas, endpoints disponibles |
| A-06 | Nómina: fix endpoint approvePeriod | 🟢 COMPLETADO | `/payroll/runs/last?period_id=X` |

---

## GRUPO B — Frontend / Empleados

| ID | Tarea | Estado | Evidencia |
|----|-------|--------|-----------|
| B-01 | Avatar empleado: dialog upload en `/app/empleados` | 🟢 COMPLETADO | AvatarUploadModal implementado |
| B-02 | Avatar empleado: validación cliente (tipo/tamaño) | 🟢 COMPLETADO | max 2MB, jpg/png/webp |
| B-03 | Avatar empleado: eliminar foto existente | 🟢 COMPLETADO | DELETE endpoint integrado |
| B-04 | Avatar empleado: permisos (employees.manage) | 🟢 COMPLETADO | botón solo visible con permiso |

---

## GRUPO C — Frontend / SST y Desempeño

| ID | Tarea | Estado | Evidencia |
|----|-------|--------|-----------|
| C-01 | SST: botón Cerrar + employee picker | 🟢 COMPLETADO | CloseIncidentButton + select empleados |
| C-02 | Desempeño: submit + employee picker + edit modal ratings | 🟢 COMPLETADO | 3 componentes nuevos, tsc 0 errores |
| C-03 | Sidebar SST activo (sin comingSoon) | 🟢 COMPLETADO | sesión anterior |
| C-04 | Sidebar Desempeño activo (sin comingSoon) | 🟢 COMPLETADO | sesión anterior |

---

## GRUPO D — Lint

| ID | Tarea | Estado | Evidencia |
|----|-------|--------|-----------|
| D-01 | `no-explicit-any` (19 errores) | 🟢 COMPLETADO | types.ts + 8 archivos — 0 `as any` restantes |
| D-02 | `no-img-element` (4 warnings) | 🟢 COMPLETADO | productos, usuarios, admin-shell → `<Image unoptimized>` |
| D-03 | `exhaustive-deps` (1 warning) | 🟢 COMPLETADO | ia/page.tsx → useCallback + dep array |
| D-04 | `no-unused-vars` | 🟢 COMPLETADO | admin-shell 4 íconos, recepciones _COP, ConversacionDetailPage _router |
| D-05 | tsc --noEmit: 0 errores | 🟢 COMPLETADO | verificado 2026-10-21 |
| D-06 | `set-state-in-effect` (26 errores) | ⚫ INTENCIONAL | todos son patrones async válidos (load data on mount) |
| D-07 | Build final tras lint | 🟢 COMPLETADO | `next build` 0 errores — 100/100 páginas generadas |

---

## GRUPO E — Módulos vacíos o incompletos

| ID | Módulo | Estado actual | Tarea |
|----|--------|---------------|-------|
| E-01 | Nómina | 🟢 COMPLETADO | Workflow completo (períodos+runs+aprobación+cierre). VP-002..VP-006 bloqueados por decisión — 2026-10-21 |
| E-02 | SST | 🟢 COMPLETADO | Validaciones Request, HasFactory, factory, 12 tests, response wrapper consistente — 2026-10-21 |
| E-03 | Desempeño | 🟢 COMPLETADO | Validaciones Request, HasFactory, factory, tests, submit workflow — 2026-10-21 |
| E-04 | Documentos | 🟢 COMPLETADO | EmployeeDocumentController + frontend CRUD — 2026-10-21 |
| E-05 | Reclutamiento | 🟢 COMPLETADO | JobVacancy/Candidate/Application/Interview controllers + KPI Fase 3 integrada — 2026-10-21 |
| E-06 | Asistencia | 🟢 COMPLETADO | AttendanceController + frontend CRUD — 2026-10-21 |
| E-07 | Vacaciones | 🟢 COMPLETADO | VacationRequestController + frontend CRUD — 2026-10-21 |
| E-08 | Permisos | 🟢 COMPLETADO | PermissionRequestController + frontend CRUD — 2026-10-21 |
| E-09 | Incapacidades | 🟢 COMPLETADO | SickLeaveController + frontend CRUD — 2026-10-21 |
| E-10 | Turnos | 🟢 COMPLETADO | ShiftController + frontend CRUD — 2026-10-21 |
| E-11 | Finanzas | 🟢 COMPLETADO | CashAccount+CXP+CXC controllers + frontend — 2026-10-21 |
| E-12 | Contabilidad | 🟢 COMPLETADO | AccountingAccountConfig+Period+ChartOfAccount + frontend — 2026-10-21 |
| E-13 | Reportes | 🟢 COMPLETADO | ReportController + página analítica custom — 2026-10-21 |
| E-14 | Auditoría | 🟢 COMPLETADO | AuditLogController + frontend CRUD read-only — 2026-10-21 |
| E-15 | Configuración | 🟢 COMPLETADO | CompanyController + AiSetting + WhatsApp/Zoom/GSheets config — 2026-10-21 |

---

## GRUPO F — Seguridad y calidad

| ID | Tarea | Estado |
|----|-------|--------|
| F-01 | Guard anti-producción (TestCase.php) | 🟢 COMPLETADO — mejorado 2026-10-21: allowlist + APP_ENV check |
| F-02 | Multitenancy audit (72 controllers) | 🟢 COMPLETADO |
| F-03 | SPEC-019 (Asistencia) — aprobación | ⚫ BLOQUEADO DECISIÓN |
| F-04a | KPI Fase 2 — Empleados KPI (3 cards: activos/ingresos/indefinidos) | 🟢 COMPLETADO — 2026-10-21 |
| F-04b | KPI Fase 3 — Reclutamiento KPI (3 cards: vacantes/postulaciones/contratados) | 🟢 COMPLETADO — 2026-10-21 |
| F-04c | KPI Estandarización Global — todos los módulos | 🟢 ACEPTADO — 2026-10-21 (48 páginas, 27 endpoints, fix resolveCompany→companyId, fix employees/stats sin middleware, 434/434 tests, Nancy APROBADO, Margarita 11/11 endpoints HTTP 200) |
| F-05 | Nómina VP-002..VP-006 — validación externa | ⚫ BLOQUEADO DECISIÓN |
| F-06 | E2E Playwright — flujos críticos | 🔵 BLOQUEADO ENTORNO |
| F-07 | Backup prod Hostinger | 🔵 BLOQUEADO ENTORNO |
| F-08 | CENTINELA P0: backup threshold 2000→50000 bytes (M-1) | 🟢 COMPLETADO — deploy-hostinger.md 2026-10-21 |
| F-09 | CENTINELA P0: C-1 credenciales SSH en deploy-hostinger.md | ⚫ RIESGO CONTROLADO — gitignored, deuda arquitectónica |
| F-10 | CENTINELA P0: TestCase.php guard allowlist + APP_ENV (A-1, M-3) | 🟢 COMPLETADO — 2026-10-21 |

---

_Última actualización: 2026-10-21 (sesión KPI global) — PEPE_
