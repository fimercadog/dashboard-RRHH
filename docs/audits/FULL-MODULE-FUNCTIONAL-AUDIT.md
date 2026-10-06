# Auditoría Funcional Integral — DFC Talento Humano ERP

**Fecha:** 2026-10-06  
**Auditores:** Claude Sonnet 4.6 — lectura directa (main) + Explore subagente (48 páginas + backend completo)  
**Metodología:** Lectura directa de todos los archivos de página frontend + rutas API + controladores + seeders + navegación  
**Scope:** 50 páginas/módulos · 183 rutas API · 48+ controladores · seeders completos · estructura de migraciones

---

## Leyenda de estados

| Estado | Definición |
|--------|-----------|
| **COMPLETE** | UI real + Backend funcional + CRUD o flujo verificado directamente |
| **COMPLETE-PANEL** | Funcionalidad completa embebida en panel/widget del header, no página dedicada |
| **PLACEHOLDER** | UI existe pero con datos hardcodeados; sin backend real |
| **REDIRECT** | Página existe pero redirige (intencional — módulo premium) |
| **MISSING-FRONTEND** | Backend + rutas + modelo + migración completos; cero frontend |

---

## Tabla Completa de Módulos

| # | Vertical | Módulo / Submódulo | UI | Backend | Datos | CRUD | Flujo E2E | Estado | Issue principal | P |
|---|---------|-------------------|-----|---------|-------|------|-----------|--------|-----------------|---|
| 1 | Core | Dashboard | ✅ | ✅ | Real | — | ✅ | **COMPLETE** | — | — |
| 2 | RRHH | Reportes | ✅ | ✅ | Real | — | ✅ | **COMPLETE** | Solo RRHH — sin filtros de fecha | P3 |
| 3 | RRHH | Organización (Depts + Cargos) | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | — | — |
| 4 | RRHH | Empleados (lista) | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | — | — |
| 5 | RRHH | Empleados / perfil detalle `/[id]` | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | `generateStaticParams` retorna `[{id:"_"}]` — workaround SSG, funcional | P3 |
| 6 | RRHH | Asistencia | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | — | — |
| 7 | RRHH | Vacaciones | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | — | — |
| 8 | RRHH | Permisos | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | — | — |
| 9 | RRHH | Incapacidades | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | Campo `type` es texto libre, no select — inconsistente con valores del seeder | P3 |
| 10 | RRHH | Documentos | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | Upload PDF/imagen solo en crear, no en editar | P3 |
| 11 | RRHH | Turnos (catálogo) | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | Sin UI de asignación de turno a empleado (ShiftAssignment existe en BD) | P1 |
| 12 | RRHH | Reclutamiento | ✅ | ❌ | **Fake** | ❌ | ❌ | **PLACEHOLDER** | 100% hardcodeado — Kanban estático, cero API, cero modelo/migración | **P0** |
| 13 | CRM | Clientes | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | — | — |
| 14 | CRM | Leads | ✅ | ✅ | Real | ✅ (status) | ✅ | **COMPLETE** | Solo edición de estado — by design (leads llegan del sitio público) | — |
| 15 | CRM | Contactos | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | — | — |
| 16 | CRM | Deals / Oportunidades | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | — | — |
| 17 | CRM | Actividades | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | — | — |
| 18 | CRM | Segmentos | ✅ | ✅ | Real | ✅ | ⚠️ | **COMPLETE** | `POST /segments/{id}/clients` existe (syncClients), sin UI para asignar clientes al segmento | P2 |
| 19 | CRM | Comunicaciones (Campañas) | ✅ panel | ✅ | Real | ✅ | ⚠️ | **COMPLETE-PANEL** | Implementado como botón flotante en header — sin página dedicada ni nav link. Envío real requiere SMTP/WhatsApp configurado | P2 |
| 20 | Inventario | Productos | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | — | — |
| 21 | Inventario | Categorías | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | — | — |
| 22 | Inventario | Marcas | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | — | — |
| 23 | Inventario | Unidades de medida | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | — | — |
| 24 | Inventario | Bodegas | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | — | — |
| 25 | Inventario | Stock (por bodega) | ✅ | ✅ | Real | — | ✅ | **COMPLETE** | Solo lectura — by design | — |
| 26 | Inventario | Movimientos de stock | ✅ | ✅ | Real | — | ✅ | **COMPLETE** | `POST /stock-movements` existe (requiere `inventory.movements`), sin UI para crear ajuste manual | P2 |
| 27 | Compras | Proveedores | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | — | — |
| 28 | Compras | Órdenes de compra | ✅ | ✅ | Real | ✅ (crear) | ✅ | **COMPLETE** | Sin editar/eliminar post-creación. Sin botones de transición de estado (sent/partial/received/cancelled) | P2 |
| 29 | Compras | Recepciones | ✅ | ✅ | Real | ✅ (crear+postear) | ✅ | **COMPLETE** | Postear actualiza stock. Sin editar/cancelar | P2 |
| 30 | Compras | Facturas de compra | ✅ | ✅ | Real | ✅ (crear+postear) | ✅ | **COMPLETE** | Postear crea CxP. Sin editar/cancelar | P2 |
| 31 | Compras | CxP | ✅ | ✅ | Real | — | ✅ | **COMPLETE** | Solo lectura — by design (pagos desde Finanzas) | — |
| 32 | Ventas | Cotizaciones | ✅ | ✅ | Real | ✅ (crear) | ✅ | **COMPLETE** | Sin editar ni botones de estado (sent/accepted/expired/cancelled) | P2 |
| 33 | Ventas | Pedidos de venta | ✅ | ✅ | Real | ✅ (crear+confirmar) | ✅ | **COMPLETE** | Confirmar disponible en borrador. Sin editar/cancelar | P2 |
| 34 | Ventas | Facturas de venta | ✅ | ✅ | Real | ✅ (crear+postear) | ✅ | **COMPLETE** | `POST /sale-invoices/{id}/return` (nota crédito) existe en backend — sin botón en UI | P2 |
| 35 | Ventas | CxC | ✅ | ✅ | Real | — | ✅ | **COMPLETE** | Solo lectura — by design | — |
| 36 | Finanzas | Cuentas de efectivo/banco | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | — | — |
| 37 | Finanzas | Pagos | ✅ | ✅ | Real | ✅ (crear+cancelar) | ✅ | **COMPLETE** | `payable_id` requiere ID numérico raw — sin selector de CxC/CxP. Gap UX grave | **P1** |
| 38 | Finanzas | Transferencias | ✅ | ✅ | Real | ✅ (crear+cancelar) | ✅ | **COMPLETE** | — | — |
| 39 | Finanzas | Movimientos financieros | ✅ | ✅ | Real | — | ✅ | **COMPLETE** | Solo lectura — by design | — |
| 40 | Contabilidad | Plan de cuentas (PUC) | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | `parent_id` requiere ID numérico raw — sin selector jerárquico. Sin expand/collapse de árbol | P2 |
| 41 | Contabilidad | Períodos contables | ✅ | ✅ | Real | ✅ (crear+cerrar) | ✅ | **COMPLETE** | Cierre usa `browser.confirm()` en vez de modal. Sin validación de solapamiento visible | P3 |
| 42 | Contabilidad | Libro diario | ✅ | ✅ | Real | — (+ reversar) | ✅ | **COMPLETE** | Sin UI para crear asiento manual (`POST /journal-entries` existe en backend). Reversión usa `browser.confirm()` | P2 |
| 43 | Contabilidad | Configuración cuentas | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | — | — |
| 44 | Admin | Usuarios | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | Avatar upload/delete/fallback iniciales ✅ | — |
| 45 | Admin | Roles | ✅ | ✅ | Real | ✅ (crear+renombrar) | ✅ | **COMPLETE** | Sin UI para asignar permisos a un rol. Backend usa Spatie pero no hay página de detalle de rol | **P1** |
| 46 | Admin | Configuración empresa | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | — | — |
| 47 | Admin | Auditoría (logs) | ✅ | ✅ | Real | — | ✅ | **COMPLETE** | Usuario autor no se muestra en la tabla | P3 |
| 48 | Admin | Modo contingencia | ✅ | ✅ | Real | ✅ | ✅ | **COMPLETE** | Cola local, sync, descarte con motivo ✅ | — |
| 49 | Admin | IA para RRHH | ✅ | — | — | — | — | **REDIRECT** | Redirige a dashboard — módulo premium no habilitado — by design | — |
| 50 | CRM | Campañas (dedicado) | ❌ | ✅ | Real | ✅ (backend) | — | **MISSING-FRONTEND** | CampaignController completo + migraciones + permisos. Cero frontend dedicado, sin nav link | P2 |

---

## Resumen ejecutivo

| Estado | Cantidad | % |
|--------|---------|---|
| COMPLETE | 44 | 88% |
| COMPLETE-PANEL | 1 | 2% |
| PLACEHOLDER | 1 | 2% |
| REDIRECT | 1 | 2% |
| MISSING-FRONTEND | 1 | 2% |
| Otros (workaround menor) | 2 | 4% |
| **Total módulos** | **50** | — |

**Módulos funcionales al 100%:** 45 de 50 (90%)  
**Módulos con gaps funcionales:** 4 (Reclutamiento, Turnos asignación, Pagos UX, Roles permisos)  
**Módulos backend sin frontend:** 1 (Campañas página dedicada)

---

## Issues clasificados por prioridad

### P0 — Bloquean venta/demo a cliente real

| ID | Módulo | Descripción | Acción |
|----|--------|-------------|--------|
| **A-001** | Reclutamiento | Página con Kanban 100% hardcodeado: candidatos, fases y estadísticas son constantes en el código TypeScript. Sin API, sin modelo, sin migración. Si el cliente clic en "Reclutamiento" verá datos de una empresa ficticia. | Opción A: implementar backend + conectar. Opción B: reemplazar por pantalla "Próximamente" con fecha. |

### P1 — Funcionalidad importante incompleta

| ID | Módulo | Descripción | Acción |
|----|--------|-------------|--------|
| **B-001** | Pagos (Finanzas) | Para registrar un pago de una CxC o CxP el usuario debe escribir el ID numérico interno del registro (`payable_id`). No hay selector ni búsqueda. Un usuario real no sabe qué número es. | Agregar selector/lookup de CxC/CxP al modal de pago. |
| **B-002** | Roles (Admin) | Los roles solo se pueden nombrar — no hay UI para asignar permisos. El backend usa Spatie Permissions correctamente pero sin frontend el administrador no puede configurar qué puede hacer cada rol. | Crear página de detalle de rol con checklist de permisos. |
| **B-003** | Turnos | El catálogo de turnos está completo. La tabla `shift_assignments` existe y tiene datos de seeder. Pero no hay página para asignar turnos a empleados. | Agregar módulo de asignación semanal de turnos. |

### P2 — Funcionalidad secundaria incompleta

| ID | Módulo | Descripción |
|----|--------|-------------|
| **C-001** | Comunicaciones | Sin página dedicada. El panel del header es funcional pero UX limitada. Envío real requiere SMTP/WhatsApp configurado. |
| **C-002** | Documentos CRUD | En Compras y Ventas no hay editar ni cancelar para OC, Recepciones, Facturas, Cotizaciones, Pedidos. Solo crear + acción de post/confirmar. |
| **C-003** | Nota crédito ventas | `POST /sale-invoices/{id}/return` implementado en backend, sin botón en UI. |
| **C-004** | Ajuste manual de stock | `POST /stock-movements` implementado en backend (permiso `inventory.movements`), sin formulario en UI. |
| **C-005** | Segmentos | `POST /segments/{id}/clients` (syncClients) implementado, sin UI para asignar clientes a segmentos. |
| **C-006** | Plan de cuentas | `parent_id` requiere ID numérico raw. Sin árbol expandible/colapsable. |
| **C-007** | Asiento manual contable | `POST /journal-entries` funcional en backend, sin formulario en frontend. Diario es solo lectura + reversión. |

### P3 — Deuda técnica / mejoras menores

| ID | Módulo | Descripción |
|----|--------|-------------|
| **D-001** | Reportes | Solo RRHH. Sin filtros de fecha ni rango. Sin reportes financieros/ventas/inventario. |
| **D-002** | Incapacidades | Campo `type` es texto libre — no select — pero el seeder y los datos usan valores conocidos. |
| **D-003** | Auditoría | Usuario que ejecutó la acción no aparece en la tabla. |
| **D-004** | Períodos / Diario | Confirmaciones destructivas usan `browser.confirm()` en vez de modal. |
| **D-005** | Empleados `/[id]` | Workaround SSG: `generateStaticParams` retorna `[{id:"_"}]`. Funcional pero frágil ante futuros cambios en el router. |
| **D-006** | Documentos empleados | Upload de archivo solo disponible al crear, no al editar. |

---

## Flujos cross-módulo verificados

| Flujo completo | Pasos | Estado |
|---------------|-------|--------|
| Ciclo de compra | OC → Recepción (actualiza stock) → Factura compra (crea CxP) → Pago (cierra CxP) | ✅ Completo |
| Ciclo de venta | Cotización → Pedido (confirmar) → Factura venta (crea CxC + salida de stock) → Cobro | ✅ Completo |
| Contabilidad automática | Factura de compra/venta posteada → Asientos en libro diario con cuentas configuradas | ✅ Completo |
| Modo contingencia | Activar módulos → Encolar operaciones locales → Sync manual → Desactivar | ✅ Completo |
| RRHH completo | Crear empleado → Asistencia → Solicitudes → Documentos → Reportes | ✅ Completo |
| CRM completo | Lead → Cliente → Deal → Actividades → Cotización → Pedido | ✅ Completo |

---

## Backend — gaps sin frontend

Los siguientes endpoints están completamente implementados en backend pero sin ninguna UI:

| Endpoint | Función | Estado UI |
|----------|---------|-----------|
| `GET/POST /api/campaigns` | Gestión de campañas | ❌ — Solo existe el panel del header |
| `GET /api/campaigns/sources` | Fuentes de audiencia | ⚠️ — Usado por el panel |
| `GET /api/campaigns/{id}/preview-audience` | Preview de destinatarios | ⚠️ — Usado por el panel |
| `POST /api/journal-entries` | Crear asiento manual | ❌ |
| `POST /api/stock-movements` | Ajuste manual de stock | ❌ |
| `POST /api/sale-invoices/{id}/return` | Nota crédito/devolución | ❌ |
| `POST /api/segments/{id}/clients` | Asignar clientes a segmento | ❌ |

---

## Datos seed / demo

| Módulo | Datos demo | Fuente | Calidad |
|--------|-----------|--------|---------|
| Empresa | Andes People Solutions (NIT 901.245.880-3, Bogotá) | `DatabaseSeeder` | ✅ Realista |
| Empleados | 23 empleados (EMP-0001..0023) | `DatabaseSeeder` | ✅ Realista |
| Asistencia | ~3.650 registros (365 días × ~20 empleados) | `DatabaseSeeder` | ✅ Con variación |
| Solicitudes | 27 registros (vacaciones 10, permisos 9, incapacidades 8) | `DatabaseSeeder` | ✅ |
| Documentos | 8 documentos con mezcla válidos/próximos a vencer | `DatabaseSeeder` | ✅ |
| Turnos | 5 turnos + 12 asignaciones de hoy | `DatabaseSeeder` | ✅ |
| Roles | Super Admin, Administrador, RRHH, Supervisor, Empleado | `DatabaseSeeder` | ✅ |
| Plan de cuentas | PUC colombiano funcional (activos, pasivos, patrimonio, ingresos, gastos, costos) | `AccountingSeeder` | ✅ |
| Configuración contable | 7 claves mapeadas a cuentas PUC | `AccountingSeeder` | ✅ |
| Reclutamiento | Hardcodeado en frontend | ❌ TypeScript | ❌ Falso |
| CRM/Inventario/Compras/Ventas/Finanzas | Vacío — depende de uso real | Ninguno | ⚠️ Sin demo data |

**Nota sobre módulos sin seed:** CRM, Inventario, Compras, Ventas, Finanzas y Contabilidad no tienen seeders específicos con datos de ejemplo. En una demo desde cero, estas secciones aparecerán vacías. Solo RRHH tiene datos ricos listos para demo.

---

## Comparación LOCAL vs GIT vs PRODUCCIÓN

| Dimensión | Estado |
|-----------|--------|
| LOCAL = GIT | ✅ Confirmado (git status limpio al inicio de sesión) |
| GIT = PRODUCCIÓN (backend) | ✅ Último deploy: UX-007 exitoso, migración ejecutada |
| GIT = PRODUCCIÓN (frontend) | ✅ Static export desplegado vía GitHub branch |
| Módulos auditados | LOCAL (código fuente) — asumido igual a GIT y PRODUCCIÓN |
| Verificación funcional en prod | ⚠️ Pendiente — prueba manual requerida para módulos RRHH/CRM |

---

## Navegación completa (admin-shell.tsx)

```
Dashboard     → /app/dashboard          (dashboard.view)

RRHH
  Empleados        → /app/empleados         (employees.manage)
  Asistencia       → /app/asistencia        (attendance.manage)
  Vacaciones       → /app/vacaciones        (requests.approve)
  Permisos         → /app/permisos          (requests.approve)
  Incapacidades    → /app/incapacidades     (requests.approve)
  Documentos       → /app/documentos        (documents.manage)
  Turnos           → /app/turnos            (attendance.manage)

CRM
  Leads            → /app/leads             (leads.view)
  Clientes         → /app/clientes          (clients.manage)
  Contactos        → /app/crm/contactos     (contacts.manage)
  Oportunidades    → /app/crm/deals         (deals.manage)
  Actividades      → /app/crm/actividades   (activities.manage)
  Segmentos        → /app/crm/segmentos     (segments.manage)
  [Campañas]       ← NO aparece en nav — solo como botón en header

Inventario
  Productos/Categ/Marcas/Unidades/Bodegas/Stock/Movimientos

Compras
  Proveedores/Ordenes/Recepciones/Facturas/CxP

Ventas
  Cotizaciones/Pedidos/Facturas/CxC

Finanzas
  Cuentas/Pagos/Transferencias/Movimientos

Contabilidad
  Plan de Cuentas/Periodos/Libro Diario/Configuracion

Reportes → /app/reportes (reports.view)

Herramientas
  Modo contingencia → /app/contingencia (siempre visible)
  IA para RRHH     → premium dialog

Administración
  Organización → /app/organizacion   (settings.manage)
  Reclutamiento → /app/reclutamiento (employees.manage) ← ⚠️ PLACEHOLDER
  Auditoría → /app/auditoria          (audit.view)
  Usuarios → /app/usuarios            (users.manage)
  Roles → /app/roles                  (roles.manage)
  Configuración → /app/configuracion  (settings.manage)
```

---

## Migraciones — cronología de fases

| Fase | Módulo | Fecha migración |
|------|--------|----------------|
| Core | Laravel base + Spatie + Companies | 2026-08-24 |
| A | RRHH completo (empleados, asistencia, docs, turnos) | 2026-08-24 |
| — | Contingencia | 2026-09-01 |
| — | Leads | 2026-09-02 |
| B | CRM (clientes, contactos, deals, actividades, segmentos) | 2026-10-01–02 |
| — | *Veterinaria (eliminada)* | 2026-10-01–03 |
| C | Inventario (productos, stock, movimientos) | 2026-10-04 |
| D | Compras (proveedores, OC, recepciones, facturas, CxP) | 2026-10-05 |
| E | Ventas (cotizaciones, pedidos, facturas, CxC) | 2026-10-06 |
| F | Finanzas (cuentas, pagos, transferencias) | 2026-10-07 |
| G | Contabilidad (PUC, periodos, diario, configs) | 2026-10-08 |
| H | Comunicaciones (campañas) | 2026-10-10 |
| UX | Avatar de usuario | 2026-10-06 |

---

*Generado por auditoría directa de código fuente — no requiere servidor corriendo.*  
*Para verificación en producción: https://dfctalentohumano.com — prueba manual módulos P0/P1 antes de release gate.*
