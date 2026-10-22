# FidelOS — Workflow

## Flujo obligatorio

Toda orden de programación:

PEPE
→ GRAPHIFY
→ CRITERIOS
→ JUAN
→ PRUEBAS
→ NANCY
→ MARGARITA / PLAYWRIGHT
→ REGRESIÓN
→ VERIFIED

## Correcciones

Si falla cualquier etapa:

JUAN
→ PRUEBAS
→ NANCY
→ MARGARITA
→ REGRESIÓN

No avanzar hasta VERIFIED.

## Pruebas

Se seleccionan según las capas afectadas:

- Frontend
- Backend
- BD
- API
- Services
- Integration
- Unit
- Feature
- Component
- Permissions
- E2E

## Contraorden

Una orden nueva que contradice una decisión vigente debe ser detectada
y explicada antes de ejecutarse.

## Orden incompleta

Si el contexto permite resolverla inequívocamente:
continuar e informar.

Si existen varias interpretaciones posibles:
preguntar únicamente lo indispensable.

## Estado final

Solo:

VERIFIED

significa que una tarea está terminada.

La documentación nunca sustituye la ejecución real de pruebas.

---

## Checklist de continuidad (SPEC-CONT-001 Fase 1)

### Antes de iniciar cualquier tarea de desarrollo

- [ ] `git status` limpio (sin cambios flotantes)
- [ ] Suite SQLite en verde: `php artisan test` — 0 fallos
- [ ] Leer `docs/PENDIENTES.md` para retomar estado correcto
- [ ] Leer `docs/CORE_CONTRACT.md` si la tarea toca contratos congelados

### Durante la implementación

- JUAN ejecuta `php artisan test` al terminar cada unidad lógica
- Si una prueba existente rompe → parar, no avanzar hasta resolver
- Cambios de BD siempre en migraciones nuevas; nunca editar migraciones publicadas

### Al cerrar cada tarea (PEPE registra en PENDIENTES.md)

- Commits generados (hash + descripción)
- Resultado de pruebas ejecutadas
- Estado del deploy si aplica
- Riesgos abiertos y próximo paso

### Rollback rápido de código (local no publicado)

```bash
git revert <hash> --no-commit   # revierte, deja staging para revisar
git commit -m "revert: <motivo>"
```

No usar `git reset --hard` en ramas publicadas.

### Suite MySQL (gate de release)

Requiere `backend/phpunit.mysql.xml` (gitignored — credenciales locales).
Plantilla: `backend/phpunit.mysql.xml.example` (committida, sin credenciales).

```bash
cp backend/phpunit.mysql.xml.example backend/phpunit.mysql.xml
# editar DB_USERNAME / DB_PASSWORD
vendor/bin/phpunit -c phpunit.mysql.xml --no-coverage
```

Ejecutar antes de cualquier push que incluya cambios de migraciones o lógica de BD.
