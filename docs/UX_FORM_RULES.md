# UX_FORM_RULES — Reglas heredables

- UX-001 Relaciones: usar select/búsqueda con etiquetas humanas; no pedir IDs técnicos. El backend valida existencia y company scope.
- UX-002 Archivos: carga real, validar tipo/tamaño, almacenar ruta relativa y exponer URL por Resource; no pedir rutas como texto.
- UX-003 Estado: definir valor inicial; estado del sistema de solo lectura cuando el usuario no puede elegirlo.
- UX-004 Valores finitos: select con opciones explícitas, no texto libre.
- UX-005 Selectores: devolver solo registros visibles para la empresa autenticada; validar también en backend.
- UX-006 Tablas: N.º como primera columna, secuencial e independiente del ID; numeración = offset de página + índice + 1.
- UX-007 Avatar: carga, vista previa, reemplazo/eliminación, fallback de iniciales y validación.

Criterios comunes: loading/vacío/error/éxito, mensajes claros, teclado/foco, responsive, persistencia real y pruebas de permisos/casos límite. Las reglas se aplican a todas las verticales.
