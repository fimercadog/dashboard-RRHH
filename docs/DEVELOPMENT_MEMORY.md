# DEVELOPMENT_MEMORY — Historial y evidencia

## 2026-10-11 — Mantenimiento documental

**Repositorio:** fimercadog/dashboard-RRHH, rama master.  
**Último commit observado antes de esta revisión:** `1c7c51a49e21e4e544dfa7699d5d0c9778ef7be7` (7 de octubre de 2026).  
**Commit anterior relevante:** `3e77d153364cfbdceb1e7a8a9c4ee6afcece1703`, corrección de permisos destructivos en RRHH.

### Cambios que constan en los commits
- Badges unificados y normalización de rutas activas del sidebar.
- Dictado de voz opt-in mediante Web Speech API.
- Reclutamiento: vacantes, candidatos, postulaciones y entrevistas.
- Límites de paginación, índices compuestos y refactor de consulta de cumpleaños.
- Corrección RBAC: separación de permisos de eliminación frente a `manage`/`approve`. El mensaje del commit anterior reporta 192/192 pruebas y respuestas 404 para IDOR cross-company.

### Evidencia de esta revisión
- IMPLEMENTADO: los cambios aparecen en el historial inspeccionado.
- VERIFICADO: no certificado; no se ejecutaron pruebas localmente.
- E2E VERIFICADO: no certificado; no se ejecutó Playwright ni navegador real.
- CI: la API de GitHub devolvió cero status checks, cero check runs y cero workflow runs asociados al commit consultado. No equivale a que las pruebas hayan fallado.
- DONE / LISTO PARA USUARIO: no certificado.

### Brechas documentales detectadas
- `CORE_CONTRACT.md` referenciaba `docs/UX_FORM_RULES.md` y `docs/VERTICAL_TEMPLATE.md`, ausentes en la rama revisada.
- Las especificaciones `SPEC-VOICE-001` y `SPEC-014` no existían como documentos independientes.
- `.claude/agents/spec-planner.md` referenciaba `docs/PROJECT_STATE.md`, `docs/DECISIONS.md` y `docs/KNOWN_ISSUES.md`, ausentes del árbol revisado.
- `CORE_CONTRACT.md` referenciaba `docs/roles-permissions.md`, también ausente.
- No existían `DEFINITION_OF_DONE.md` ni `CORE_INHERITANCE_REGISTRY.md`.

### Lecciones generalizables
1. `manage` no implica `delete`; `approve` no implica `delete`. Las operaciones destructivas requieren permisos explícitos y pruebas de acceso entre empresas.
2. Verificar en el repositorio las rutas de documentos y specs citados por contratos y agentes.
3. Un mensaje de commit que informa pruebas pasadas no reemplaza resultados de CI ni una verificación independiente actual.
4. Separar reglas estables, historial fechado, contrato CORE, registro de herencia y criterios de aceptación.

### Riesgo abierto
No se verificó el producto desplegado ni se ejecutaron pruebas en este mantenimiento. No inferir que la versión pública coincide con este commit.
