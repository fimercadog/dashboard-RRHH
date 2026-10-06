# WORKFLOW — Flujo obligatorio de desarrollo FidelOS

## 1. Regla principal
Todo prompt del usuario se analiza antes de actuar.

Si implica desarrollo o modificación de código, entra SIEMPRE en este flujo, aunque la tarea sea mínima.
La profundidad cambia según el riesgo; la verificación nunca desaparece.

PROMPT → PEPE → GRAPHIFY → CRITERIOS → JUAN → PRUEBAS → NANCY → MARGARITA/PLAYWRIGHT → REGRESIÓN → VERIFIED

No avanzar a la siguiente tarea mientras la actual no esté VERIFIED.

## 2. Roles

### PEPE — Orquestador
- Recibe y clasifica todo prompt.
- Determina si es desarrollo, consulta o acción operativa.
- Revisa contexto, código real y Graphify antes de modificar.
- Convierte la petición en historia/criterios verificables cuando haga falta.
- Asigna el trabajo a Juan.
- Decide qué pruebas corresponden.
- Recibe resultados de Nancy y Margarita.
- Reabre la tarea cuando cualquier verificación falla.
- Solo declara VERIFIED cuando todos los gates pasan.
- No debe modificar funcionalidad de la aplicación para saltarse el proceso.

### JUAN — Implementador
- Modifica el código funcional.
- Implementa todas las capas afectadas.
- Ejecuta y corrige las pruebas técnicas.
- No puede aprobar su propio trabajo como VERIFIED.

### NANCY — QA adversarial
- No implementa correcciones.
- Comprueba cada criterio de aceptación.
- Busca regresiones funcionales, errores de UI, permisos, validaciones, estados, responsive y rutas.
- Si algo falla, devuelve defecto reproducible a Juan.

### MARGARITA — E2E y regresión
- Usa Playwright para comprobar la historia completa como usuario real.
- Comprueba el flujo de principio a fin.
- Ejecuta regresión de los flujos afectados y del smoke crítico.
- Al cerrar una fase/conjunto de tareas, ejecuta la regresión completa definida para el proyecto.
- No implementa correcciones.

## 3. Criterios de aceptación
Toda funcionalidad debe tener criterios verificables.

Ejemplo: “botón verde” no significa solamente que exista.

Debe verificarse, según alcance:
- existe y es visible
- texto correcto
- color/estilo correcto
- responsive
- permisos correctos
- interacción correcta
- estados de loading/error
- llamada API correcta
- servicio correcto
- persistencia BD correcta
- efecto funcional correcto
- no rompe funcionalidades relacionadas

## 4. Pruebas técnicas
Se ejecutan las capas aplicables al cambio:
- Backend: Unit / Feature
- Frontend: Unit / Component
- BD: migrations / constraints / integridad
- API: Feature / contract según corresponda
- Services: Unit / Integration
- Authorization: permisos y aislamiento de company
- Integration: cruces entre módulos

No todas las tareas requieren crear pruebas de todas las capas, pero todas las capas afectadas deben quedar verificadas.

## 5. E2E
Las historias de usuario deben tener cobertura Playwright cuando el flujo sea de usuario.

Una historia no queda completa porque sus endpoints funcionen: debe comprobarse el recorrido real de usuario cuando aplique.

## 6. Ciclo de rechazo
Si cualquier prueba o revisión falla:

NANCY/MARGARITA → defecto reproducible → JUAN corrige → pruebas técnicas → NANCY → MARGARITA

Repetir hasta PASS.

## 7. Gate de cierre
Una tarea solo puede pasar a VERIFIED cuando:
- criterios de aceptación PASS
- pruebas técnicas aplicables PASS
- Nancy PASS
- Margarita/Playwright PASS cuando corresponda
- regresión requerida PASS
- build/quality checks PASS

“Implementado” ≠ “probado” ≠ “aprobado”.

## 8. Orden de tareas
Una tarea VERIFIED queda cerrada y recién entonces se inicia la siguiente.

## 9. Contraórdenes
Si una nueva orden contradice una decisión vigente, un contrato CORE, una historia abierta o una regla técnica:
- detener la ejecución de la parte contradictoria
- informar “CONTRAORDEN DETECTADA”
- indicar qué regla/decisión contradice
- explicar la consecuencia
- pedir confirmación solo cuando realmente cambia una decisión vigente

Si el usuario dice explícitamente que la decisión anterior queda reemplazada, actualizar la decisión y continuar.

## 10. Órdenes incompletas
- Si el contexto existente permite saber exactamente qué hacer: ejecutar sin preguntar e informar lo realizado.
- Si falta información indispensable: indicar exactamente qué falta.
- Si hay dos o más interpretaciones razonables con resultados distintos: preguntar antes de modificar código.
- No repetir preguntas cuya respuesta ya está en el proyecto, memoria, decisiones, historias o código.

## 11. Fuente de verdad
- Código real + tests + Playwright = evidencia de estado.
- CORE_CONTRACT.md = contratos técnicos que no deben romperse.
- DECISIONS.md = decisiones vigentes.
- MEMORY.md = aprendizajes/ reglas técnicas vigentes y validadas.
- USER_STORIES/ = comportamiento esperado y aceptación.
- WORKFLOW.md = proceso.

Los documentos NO pueden declarar “completo” un módulo si las pruebas reales no lo demuestran.
