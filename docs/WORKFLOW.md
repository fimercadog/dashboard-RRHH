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