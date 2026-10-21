# Pendientes activos — DFC Talento Humano

> Última actualización: 2026-10-20

---

## Commits pendientes (sin push)

- [ ] Commit: `DatabaseSeeder.php` + `admin-shell.tsx` — Diego Salazar seeder + unificación sidebar "Recursos Humanos"
- [ ] Push: commit `a38c751` (KpiCard + statsSlot) — existe solo localmente

---

## SPEC-019 — Asistencia y ausentismo

- [ ] Aprobación de implementación (en pausa hasta resolver continuidad)
  - E-1 resuelta: `/app/asistencia/estadisticas` se conserva como página independiente con botón "Indicadores"
  - E-2 resuelta: KpiCard local de estadísticas se conserva; migración al compartido queda para fase posterior
- [ ] Verificar que controladores backend rechazan transiciones de estado inválidas (no solo ocultar botones en frontend)

---

## SPEC-CONT-001 — Continuidad, backups y rollback

- [ ] Revisión del informe de auditoría entregado (2026-10-20) — 4 fases propuestas, ninguna implementada
- [ ] Fase 1: push trabajo actual + plantilla de continuidad en WORKFLOW.md + regla en CLAUDE.md
- [ ] Fase 2: verificar backup automático en Hostinger (SSH read) + documentar backup manual
- [ ] Fase 3: guard en script de deploy — backup de BD antes de `migrate --force`
- [ ] Fase 4: tags de release + suite MySQL en phpunit.xml

### Riesgos críticos sin resolver

- [ ] **CRÍTICO** — Sin backup verificado de BD de producción
- [ ] **CRÍTICO** — Script de deploy ejecuta `migrate --force` sin backup previo
- [ ] **ALTO** — Sin pruebas en MySQL (solo SQLite en memoria)
- [ ] **ALTO** — Sin CI/CD (cero automatización en push/PR)

---

## SPEC-018 — Nómina

- [ ] VP-002..VP-006 — cálculos de nómina bloqueados para producción hasta validación profesional externa

---

## Fases KPI — pendientes de autorización independiente

- [ ] Fase 2 — Empleados KPI
- [ ] Fase 3 — Reclutamiento KPI
- [ ] Fase 4 — Asistencia KPI (depende de SPEC-019)
- [ ] Fase 5 — Documentos KPI
- [ ] Fase 6 — Nómina (página primero)
- [ ] Fase 7 — SST y Desempeño (módulos primero)

---

## Módulos sin SPEC

- [ ] **Empresa cliente** — modelo crítico del que dependen otros módulos
- [ ] **SST** — sin página ni SPEC
- [ ] **Desempeño** — sin página ni SPEC

---

## Regla de uso

Este archivo se actualiza al inicio y al cierre de cada sesión.
Nunca reemplaza evidencia real de pruebas ni el estado de Git.
