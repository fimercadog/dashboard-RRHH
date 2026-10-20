# DFC Talento Humano — Problemas conocidos y deuda técnica

> Última actualización: 2026-10-20

---

## KI-001 — Sin límite máximo de rango de fechas en `GET /api/attendance-stats`

| Campo | Detalle |
|---|---|
| **Archivo** | `backend/app/Http/Controllers/Api/AttendanceStatsController.php:26-27` |
| **Detectado** | 2026-10-20 — CENTINELA, auditoría SPEC-017 |
| **Severidad** | LOW |
| **Estado** | **RESUELTO** — 2026-10-20. Límite de 366 días definido e implementado (Fidel, SPEC-017 ajustes finales). |

### Descripción

El endpoint `GET /api/attendance-stats` acepta cualquier rango de fechas entre `from` y `to` sin límite máximo. Solo se valida que `to >= from`. Un rango muy amplio (p. ej. varios años) produce un `SELECT` con JOINs sobre toda la tabla `attendances` para la empresa, lo que puede ser costoso en instancias con historial extenso.

### Impacto

- **Rendimiento:** para empresas con alta rotación y muchos registros de asistencia, un rango de 3–5+ años podría tardar varios segundos y consumir memoria significativa en el proceso PHP.
- **DoS interno:** un usuario autenticado con `attendance.manage` puede lanzar repetidamente consultas de rango amplio, degradando el servidor para otros usuarios de la misma instancia.
- **No es explotable externamente** sin autenticación + permiso.

### Mitigación existente

- El endpoint requiere `auth:sanctum` + `can:attendance.manage` — no es accesible sin credenciales válidas.
- El índice `(company_id, date, status)` en `attendances` cubre la consulta; el impacto real depende del volumen de datos.

### Resolución aplicada (2026-10-20)

Fidel definió el límite en **366 días** (cubre un año completo incluyendo años bisiestos). Implementado como closure validator en `AttendanceStatsController::__invoke()`:

```php
function ($attribute, $value, $fail) use ($request) {
    $from = $request->input('from');
    if (! $from || ! strtotime($from)) { return; }
    $maxTo = \Carbon\Carbon::parse($from)->addDays(365)->toDateString();
    if ($value > $maxTo) {
        $fail('El rango máximo permitido es de 366 días.');
    }
},
```

Tests: `range_of_366_days_is_accepted` y `range_of_367_days_returns_422` — ambos pasan.

### Criterio de cierre — CUMPLIDO

(1) Fidel definió 366 días. (2) Validación implementada y probada. (3) Este documento actualizado.

---

*Añadir nuevos ítems con prefijo `KI-NNN` en orden cronológico.*
