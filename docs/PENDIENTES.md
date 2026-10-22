# Pendientes activos — DFC Talento Humano

> Última actualización: 2026-10-22 (MISIÓN FINAL INTEGRAL completada)
> Estado git: árbol limpio, origin/master sincronizado, HEAD `950c0cc`

---

## Categoría A — Pendientes técnicos locales

Trabajo que PEPE puede ejecutar sin intervención externa.

| Prioridad | Tarea | Estado |
|-----------|-------|--------|
| ✅ CERRADO | **KI-004 — Lint ESLint** — 26 errores `react-hooks/set-state-in-effect` | Resuelto 2026-10-22 — 0 errores. Commit `05491b3`. |
| 🟡 DEUDA | **SPEC-CONT-002** — Protocolo de continuidad: validar rollback/recuperación paso a paso | Draft existe. Después de R1+R2 verificados. |
| 🔵 INFRA | **CI/CD** — cero automatización en push/PR | Decisión de infraestructura pendiente de Fidel. |
| ⚫ FUTURO | **KipiCard compartido** — `asistencia/estadisticas` usa KpiCard local (por diseño SPEC-019). Migrar al compartido cuando SPEC-019 sea aprobada. | Bloqueado por decisión SPEC-019. |

### Registro histórico — tareas cerradas (sesión 2026-10-22)

| Tarea | Resultado |
|-------|-----------|
| PHPUnit 11 → atributos `#[Test]` | CERRADO |
| IDOR crítico UserController::store() (H1) | CERRADO |
| Roles per-company — migración + RoleController (H2) | CERRADO |
| H-UC1/UC2 — IDOR employee_id + avatar_path mass-assignment | CERRADO |
| E2E Playwright 12/12 (login, CRUD, permisos, exports) | CERRADO |
| Push 9 commits a origin/master | CERRADO |

---

## Categoría B — Decisiones funcionales (requieren Fidel)

Trabajo bloqueado esperando una decisión de diseño o prioridad.

| ID | Decisión pendiente | Consecuencia de posponer |
|----|-------------------|--------------------------|
| **SPEC-019** | Aprobar implementación de nueva pantalla de indicadores de asistencia (KpiCard compartido + restructura de `/app/asistencia`) | El KpiCard local de `estadisticas` permanece — es funcional pero no sigue el patrón estándar. |
| **CI/CD** | Elegir plataforma de CI (GitHub Actions ya parcialmente configurado) y definir gates mínimos | Cada push queda sin validación automática. |
| **Nómina prioridad** | Confirmar si la Fase A de SPEC-018 es la siguiente prioridad de desarrollo o hay otro módulo antes | SPEC-018 está listo para implementar en cuanto se dé la orden. |
| **Retención integral VP-006** | Decidir cómo presentar al usuario la advertencia de salario integral | El sistema pone `withholding_tax = 0` con badge rojo — puede ser suficiente o puede requerir bloqueo. |

---

## Categoría C — Acciones externas (requieren actores fuera del equipo)

Trabajo que no puede avanzar sin intervención de Fidel en sistemas externos o de un profesional externo.

### C-1. Backup y restauración (R1/R2)

#### R1 — Verificar backup real en producción

**Estado:** BLOQUEADO — requiere primer deploy con el script actualizado.

**Acción concreta:**
1. Hacer deploy a Hostinger (SSH `u910322706@82.29.157.42:65002`).
2. El script `deploy-hostinger.md` incluye el guard: `mysqldump` → `.sql.tmp` → validación 50 000 bytes mínimo → rename a `.sql`.
3. Después del deploy, verificar en el servidor:
   ```bash
   ls -lh ~/backups/
   # Debe aparecer un archivo .sql con tamaño > 50 KB
   ```
4. Confirmar que el archivo no está vacío y contiene DDL válido:
   ```bash
   head -20 ~/backups/dfc_backup_YYYY-MM-DD_HHMM.sql
   # Debe mostrar: -- MySQL dump / CREATE TABLE ...
   ```

**Criterio de éxito R1:** archivo `.sql` en `~/backups/` con tamaño > 50 KB y contenido DDL reconocible.

#### R2 — Probar restauración en base de datos aislada

**Estado:** BLOQUEADO — requiere SSH a Hostinger y BD separada.

**Acción concreta (NUNCA sobre producción):**
1. Crear BD de prueba `u910322706_dfc_restore` en hPanel de Hostinger (si no existe).
2. Transferir el backup generado en R1:
   ```bash
   scp -P 65002 u910322706@82.29.157.42:~/backups/dfc_backup_latest.sql ./dfc_backup_restore_test.sql
   ```
3. Restaurar en la BD de prueba:
   ```bash
   mysql -h 127.0.0.1 -u u910322706_restore -p u910322706_dfc_restore < dfc_backup_restore_test.sql
   ```
4. Verificar tablas y conteo de registros:
   ```bash
   mysql -h 127.0.0.1 -u u910322706_restore -p u910322706_dfc_restore -e "SHOW TABLES; SELECT COUNT(*) FROM employees;"
   ```
5. Comparar el conteo de tablas contra producción.

**Criterio de éxito R2:** mismas tablas, mismo conteo de filas en tablas críticas (`employees`, `users`, `companies`, `payroll_runs`).

**Nota de riesgo:** hasta no ejecutar R1+R2, el backup existe en el script pero **no está verificado en producción**. Tratar la continuidad del sistema como no probada.

---

### C-2. Validaciones de nómina — SPEC-018 VP-002..VP-006

**Estado:** BLOQUEADO — requiere contador público colombiano.

Ver documento detallado: [docs/VP-MATRIZ-CONTADOR.md](VP-MATRIZ-CONTADOR.md) *(se crea en esta sesión)*.

**Resumen ejecutivo:**

| VP | Qué debe validar el contador | Efecto mientras no valida |
|----|------------------------------|---------------------------|
| VP-002 | Naturaleza jurídica de cada empresa cliente → elegibilidad exoneración Art. 114-1 ET | Bloquea cierre de nómina. |
| VP-003 | Tasa ARL real por empresa (no solo clase de riesgo) | Usa tasa mínima del rango + advertencia en comprobante. |
| VP-004 | Escala completa Fondo Solidaridad Pensional (Art. 27 Ley 100) | `solidarity_fund = 0` para empleados con IBC > 4 SMMLV + advertencia. |
| VP-005 | Tabla Art. 383 ET actualizada 2026 (rangos UVT y porcentajes) | Retención bloqueada si `uvt.verified_at = null`. Usa tabla de referencia con advertencia. |
| VP-006 | Procedimiento retención salario integral (Art. 384 ET) | `withholding_tax = 0` para contratos integrales + badge rojo. |

---

### C-3. SPEC-019 — Implementación aprobación (Fidel decide + implementa PEPE)

**Estado:** BLOQUEADO — en pausa hasta aprobación.

**Contexto:** SPEC-019 propone restructurar la página de asistencia para integrar los indicadores de estadísticas como tab dentro de `/app/asistencia` (en vez de página separada `/app/asistencia/estadisticas`). También implica migrar el KpiCard local al compartido.

**Si se aprueba:** PEPE asigna a JUAN — estimado 1 sesión de trabajo.
**Si se pospone:** funciona correctamente con la arquitectura actual. Sin deuda técnica bloqueante.

---

## SPEC-CONT-001 — Estado de continuidad

| Fase | Estado |
|------|--------|
| Fase 1: plantilla de continuidad en WORKFLOW.md + regla en CLAUDE.md | ✅ HECHO 2026-10-22 |
| Fase 2: verificar backup automático en Hostinger (SSH read) | ⛔ Bloqueado — ver C-1 R1 |
| Fase 3: guard `mysqldump` antes de `migrate --force` en deploy script | ✅ HECHO 2026-10-21 (threshold: 50 000 bytes) |
| Fase 4: suite MySQL en phpunit.mysql.xml | ✅ HECHO 2026-10-22 (439/439) |

---

## Estado de tests (última verificación 2026-10-22)

| Suite | Resultado | Entorno |
|-------|-----------|---------|
| SQLite (`php artisan test`) | ✅ 451/451 | SQLite en memoria |
| MySQL (`phpunit.mysql.xml`) | ✅ 439/439 | MariaDB local |
| E2E Playwright | ✅ 12/12 | Chromium bundled |
| TypeScript (`tsc --noEmit`) | ✅ 0 errores | — |
| Build producción (`npm run build`) | ✅ limpio | — |
| Lint ESLint | ✅ 0 errores | — |

---

## Regla de uso

Este archivo se actualiza al inicio y al cierre de cada sesión.
Nunca reemplaza evidencia real de pruebas ni el estado de Git.
