# Pendientes activos — DFC Talento Humano

> Última actualización: 2026-10-22 (sesión autónoma — corrección IDOR crítico + suite 436/436)

---

## Commits locales (sin push)

- [x] `69722a2` feat(backend-kpi): 27 endpoints stats() + fix companyId + security fixes — 2026-10-21
- [x] `944744d` feat(sst-desempeno): módulos SST e incidentes y Desempeño con evaluaciones — 2026-10-21
- [x] `a19e764` feat(frontend-kpi): KPI cards en 48 páginas — todos los módulos — 2026-10-21
- [x] `b6cb518` chore(frontend): admin-shell sidebar RRHH, crud-modal, types, conversaciones — 2026-10-21
- [x] `e1b58a2` docs(pendientes): PENDIENTES, BACKLOG, SPEC-CONT-002 e informes de sesión — 2026-10-21
- [ ] **Push pendiente** — autorización de Fidel requerida

---

## Pendientes de PEPE (acciones técnicas propias)

| Prioridad | Tarea | Estado |
|-----------|-------|--------|
| ✅ CERRADO | **PHPUnit 11 → atributos PHP** — migración `@test` → `#[Test]` en `AttendanceStatsTest` (17) y `EmployeeWorkHistoryTest` (11) | Hecho 2026-10-22 |
| ✅ CERRADO | **IDOR crítico UserController::store()** — `??=` reemplazado por `=` forzando company_id del token | Hecho 2026-10-22 — test de regresión añadido |
| ✅ CERRADO | **`dashboard/page.tsx`** — KpiCard local documentado: difiere de shared (useCountUp, tone, Delta.pct); no se migra sin refactor del shared | Documentado 2026-10-22 |
| ✅ CERRADO | **Empresa cliente IDOR/multitenancy audit** — CENTINELA ejecutó auditoría completa | Ver hallazgos abajo |
| 🟡 DEUDA | **RoleController — roles globales** — cualquier admin puede modificar roles de otras empresas; requiere decisión arquitectónica (per-company roles vs super-admin guard) | Decisión pendiente |
| 🟡 DEUDA | **`asistencia/estadisticas/page.tsx`** — KpiCard local mínimo (por diseño SPEC-019) | Pendiente aprobación SPEC-019 |
| 🟡 R4 | **SPEC-CONT-002.md** — draft de protocolo existe; falta validar rollback/recuperación paso a paso | Después de R1+R2 |
| 🔵 INFRA | **CI/CD** — cero automatización en push/PR | Decisión de infraestructura |

### Hallazgos CENTINELA 2026-10-22

| # | Severidad | Hallazgo | Estado |
|---|-----------|----------|--------|
| H1 | CRÍTICO | `UserController::store()` — `company_id` inyectable via payload (`??=` no protegía cuando el campo venía en el request) | **CORREGIDO** — `??=` → `=`, test añadido, 436/436 pass |
| H2 | ALTO | `RoleController::update()` — roles globales sin ownership check por empresa | **DEUDA DOCUMENTADA** — requiere decisión arq. |
| H3 | MEDIO | `LeadController::update()` — sin ownership check (leads son globales por diseño) | **DOCUMENTADO** — contenido por diseño; `leads.*` no debe asignarse a tenants |

---

## Pendientes que requieren acción de Fidel

| Prioridad | Tarea | Estado |
|-----------|-------|--------|
| 🔴 CRÍTICO | **R1** — Verificar backup real en Hostinger tras primer deploy: confirmar que `.sql` se genera en `~/backups/` y que el archivo no está vacío | Bloqueado — requiere deploy |
| 🔴 CRÍTICO | **R2** — Probar restauración en BD separada (`u910322706_dfc_restore`): restaurar, verificar tablas y registros. Nunca restaurar sobre producción | Bloqueado — requiere acción manual |
| 🔵 ENTORNO | **E2E Playwright** — conectar extensión Chrome en `chrome://extensions` para desbloquear flujos E2E | Bloqueado — extensión no conectada |
| ⚫ DECISIÓN | **SPEC-018 VP-002..VP-006** — cálculos de nómina bloqueados hasta validación profesional externa | En espera |
| ⚫ DECISIÓN | **SPEC-019 (Asistencia)** — implementación en pausa hasta aprobación | En espera |
| 🟡 LISTO | **`git push`** — 5 commits locales listos (`69722a2`..`e1b58a2`), árbol limpio | Esperando autorización |

---

## SPEC-019 — Asistencia y ausentismo

- [ ] Aprobación de implementación (en pausa hasta resolver continuidad)
  - E-1 resuelta: `/app/asistencia/estadisticas` se conserva como página independiente con botón "Indicadores"
  - E-2 resuelta: KpiCard local de estadísticas se conserva; migración al compartido queda para fase posterior
- [x] Verificar que controladores backend rechazan transiciones de estado inválidas — **verificado**: `AttendanceController` + módulo funcional completo

---

## SPEC-CONT-001 — Continuidad, backups y rollback

- [x] Revisión del informe de auditoría entregado (2026-10-20) — 4 fases propuestas
- [x] Fase 1: plantilla de continuidad en WORKFLOW.md + regla en CLAUDE.md — hecho 2026-10-22
- [ ] Fase 2: verificar backup automático en Hostinger (SSH read) + documentar backup manual — bloqueado
- [x] Fase 3: guard en script de deploy — mysqldump antes de `migrate --force` — hecho 2026-10-21
  - [x] Threshold backup corregido: 2000 → 50000 bytes (alineado con SPEC R1) — 2026-10-21
- [x] Fase 4: suite MySQL en phpunit.xml + phpunit.mysql.xml.example committido — hecho 2026-10-22

### Soluciones implementadas (2026-10-21)

- **TestCase.php**: guard anti-producción mejorado — ahora usa **allowlist** (`[:memory:, dfc_test, dfc_testing, testing]` + sufijos `_test`/`_testing`) en lugar de blocklist con "prod". Se agrega también verificación de `APP_ENV=production`. Hallazgo CENTINELA A-1 y M-3 — CERRADO.
- **deploy-hostinger.md**: threshold de verificación de tamaño de backup corregido de 2000 → 50000 bytes. Hallazgo CENTINELA M-1 — CERRADO.
- **C-1 (CRÍTICO CONTROLADO)**: credenciales SSH en `deploy-hostinger.md` — archivo gitignored, riesgo conocido. Deuda arquitectónica: mover a `.env.deploy`. No bloqueante para desarrollo local.

### Riesgos críticos

- [ ] **CRÍTICO** — Backup verificado de BD de producción: pendiente ejecutar primer deploy con guard para confirmar que crea el .sql en ~/backups/
- [x] **CRÍTICO** — Script de deploy ejecuta `migrate --force` sin backup: **resuelto** — guard en deploy-hostinger.md
- [x] **ALTO** — TestCase.php guard con blocklist débil: **resuelto** — ahora allowlist + APP_ENV — 2026-10-21
- [ ] **ALTO** — Sin CI/CD (cero automatización en push/PR)
- [x] Sin pruebas en MySQL (solo SQLite en memoria): **en progreso** — suite MySQL corriendo (phpunit.mysql.xml, MariaDB 10.4 local restaurado de backup de corrupción Aria)

---

## SPEC-018 — Nómina

- [ ] VP-002..VP-006 — cálculos de nómina bloqueados para producción hasta validación profesional externa
- [x] Página de nómina y workflow completo (períodos, runs, aprobación, cierre) — **verificado funcional** 2026-10-21

---

## Fases KPI

- [x] Fase 2 — Empleados KPI — **COMPLETADO** 2026-10-21
  - Backend: `EmployeeController::stats()` — total activos, ingresos este mes, por tipo de contrato
  - Frontend: 3 cards en `/app/empleados` vía `statsSlot`
- [x] Fase 3 — Reclutamiento KPI — **COMPLETADO** 2026-10-21
  - Backend: `JobVacancyController::stats()` — vacantes abiertas, postulaciones activas, contratados este mes
  - Frontend: 3 cards en `/app/reclutamiento/vacantes` vía `statsSlot`
- [x] Fase 4 — Asistencia KPI — **COMPLETADO** 2026-10-21 (`/attendance-stats`, 4 cards)
- [x] Fase 5 — Documentos KPI — **COMPLETADO** 2026-10-21 (`/employee-documents/stats`, 3 cards: total/vencidos/por_vencer)
- [x] Fase 6 — Nómina KPI — **COMPLETADO** 2026-10-21 (`/payroll/periods/stats`, 4 cards: total/borrador/aprobados/cerrados)
- [x] Fase 7 — SST y Desempeño KPI — **COMPLETADO** 2026-10-21 (4 cards cada uno)
- [x] **Estandarización Global KPI** — **COMPLETADO** 2026-10-21
  - 16 módulos con KPI cards (Sesión 1): Vacaciones, Permisos, Incapacidades, Documentos, Asistencia, Turnos, SST, Desempeño, Candidatos, Postulaciones, Entrevistas, Nómina, Finanzas (cuentas/CXC/CXP), Contabilidad (plan/períodos)
  - 16 endpoints backend `stats()` nuevos + rutas en api.php
  - tsc: 0 errores | build: limpio
- [x] **Estandarización Global KPI — Fase 2 (CRM/ERP/Inventario)** — **ACEPTADO** 2026-10-21
  - 48 páginas con KpiCards confirmadas (Nancy: 46 import compartido + 2 locales por diseño: dashboard, asistencia/estadisticas)
  - 27 endpoints stats en api.php (todos antes de su apiResource, todos auth:sanctum)
  - Fix crítico: `/employees/stats` faltaba `->middleware('can:employees.view')` — corregido en esta auditoría
  - Fix masivo previo: 24 controllers corregidos de `resolveCompany()` → `companyId(Request $request)`
  - Tests: 434/434 pass post-fix
  - tsc: 0 errores
  - Margarita: 11/11 endpoints verificados HTTP 200 con datos reales (browser visual bloqueado: Chrome extension no conectada)
  - Nancy: APROBADO — 27/27 endpoints, 48/48 páginas, excepciones Lead/Role documentadas y correctas
  - 18 páginas excluidas justificadas (configuraciones, integraciones, páginas de detalle/hub)

---

## Módulos sin SPEC — ACTUALIZADOS

- [ ] **Empresa cliente** — modelo crítico del que dependen otros módulos — pendiente de auditoría
- [x] **SST** — **COMPLETADO** 2026-10-21
  - Backend: `SstIncidentController` + `StoreSstIncidentRequest` (validaciones), `SstIncidentFactory`, `HasFactory` en modelo, response wrapper consistente
  - Frontend: tabla + modal creación/edición + botón Cerrar con empleado responsable
  - Tests: `SstPerformanceTest.php` (12 tests: CRUD, multitenancy, close flow)
- [x] **Desempeño** — **COMPLETADO** 2026-10-21
  - Backend: `PerformanceReviewController` + `StorePerformanceReviewRequest` (validaciones), `PerformanceReviewFactory`, `HasFactory` en modelo, submit workflow
  - Frontend: tabla + modal creación/edición + ratings + botón Enviar evaluación
  - Tests: incluidos en `SstPerformanceTest.php`

---

## Estado de tests

| Suite | Estado | Resultado |
|-------|--------|-----------|
| SQLite (phpunit.xml) | ✅ PASS | 436/436 pass, 1090 assertions (2 tests nuevos UserControllerTest) — 2026-10-22 |
| MySQL (phpunit.mysql.xml) | ✅ PASS | 426/426 pass, 1057 assertions, exit 0 — 2026-10-22 |
| E2E Playwright | BLOQUEADO ENTORNO | Pendiente configuración Chrome extension |

---

## Evaluaciones de herramientas

- [x] **Headroom** (headroomlabs-ai/headroom) — evaluado 2026-10-21, diferido.
  Python 3.13 no disponible (solo 3.12.6). context-mode cubre input tokens.
  Output shaper no demostrado en este entorno de Claude Code.
  Reabrir si hay evidencia concreta de consumo excesivo de tokens de salida.

---

## Regla de uso

Este archivo se actualiza al inicio y al cierre de cada sesión.
Nunca reemplaza evidencia real de pruebas ni el estado de Git.
