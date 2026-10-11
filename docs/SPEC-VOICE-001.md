# SPEC-VOICE-001 — Dictado de voz

## Objetivo
Permitir dictado opt-in en campos seleccionados sin romper el formulario en navegadores incompatibles.

## Contrato
- Usar Web Speech API; preferir es-CO y permitir fallback es-ES.
- Mostrar control solo en campos habilitados explícitamente.
- Detectar soporte antes de mostrar el control; el campo normal sigue funcionando sin soporte.
- Gestionar inicio, detención, permisos y desmontaje.
- Sincronizar transcripción con el estado real del formulario y el valor enviado a la API.

## Aceptación
Probar campo habilitado, campo no habilitado, navegador sin soporte, permiso denegado, detener y desmontar. Ejecutar pruebas de componente, build/typecheck y E2E pertinentes antes de marcar VERIFICADO.

## Estado al 2026-10-11
El commit 1c7c51a49e21e4e544dfa7699d5d0c9778ef7be7 describe la implementación. No se ejecutaron pruebas en este mantenimiento; VERIFICADO, E2E VERIFICADO y DONE no están certificados.