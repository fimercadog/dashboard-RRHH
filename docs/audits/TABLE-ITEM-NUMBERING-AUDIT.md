# TABLE ITEM NUMBERING AUDIT — UX-006

**Fecha:** 2026-10-06
**Regla:** UX-006 — Toda tabla/listado de registros visible al usuario debe mostrar numeración visual secuencial. No es el ID de BD.
**Implementación CORE:** `DataTable` component (`frontend/src/components/data-table/data-table.tsx`)
**Formula paginación:** `offset = (page-1) * (meta.per_page ?? 15)`, celda = `offset + rowIndex + 1`

---

## TABLAS VIA CORE (DataTable / ModuleTablePage) — AUTOMÁTICAS

| Módulo | Archivo | Estado |
|--------|---------|--------|
| RRHH — Empleados | `app/empleados/page.tsx` | ✅ CORE |
| RRHH — Asistencia | `app/asistencia/page.tsx` | ✅ CORE |
| RRHH — Vacaciones | `app/vacaciones/page.tsx` | ✅ CORE |
| RRHH — Permisos | `app/permisos/page.tsx` | ✅ CORE |
| RRHH — Incapacidades | `app/incapacidades/page.tsx` | ✅ CORE |
| RRHH — Documentos | `app/documentos/page.tsx` | ✅ CORE |
| RRHH — Turnos | `app/turnos/page.tsx` | ✅ CORE |
| RRHH — Organización (Depts + Cargos) | `app/organizacion/page.tsx` | ✅ CORE |
| CRM — Clientes | `app/clientes/page.tsx` | ✅ CORE |
| CRM — Leads | `app/leads/page.tsx` | ✅ CORE |
| CRM — Contactos | `app/crm/contactos/page.tsx` | ✅ CORE |
| CRM — Actividades | `app/crm/actividades/page.tsx` | ✅ CORE |
| CRM — Deals | `app/crm/deals/page.tsx` | ✅ CORE |
| CRM — Segmentos | `app/crm/segmentos/page.tsx` | ✅ CORE |
| Inventario — Productos | `app/inventario/productos/page.tsx` | ✅ CORE |
| Inventario — Categorías | `app/inventario/categorias/page.tsx` | ✅ CORE |
| Inventario — Marcas | `app/inventario/marcas/page.tsx` | ✅ CORE |
| Inventario — Unidades | `app/inventario/unidades/page.tsx` | ✅ CORE |
| Inventario — Bodegas | `app/inventario/bodegas/page.tsx` | ✅ CORE |
| Inventario — Movimientos | `app/inventario/movimientos/page.tsx` | ✅ CORE |
| Inventario — Stock | `app/inventario/stock/page.tsx` | ✅ CORE |
| Compras — Proveedores | `app/compras/proveedores/page.tsx` | ✅ CORE |
| Compras — Órdenes | `app/compras/ordenes/page.tsx` | ✅ CORE |
| Compras — Recepciones | `app/compras/recepciones/page.tsx` | ✅ CORE |
| Compras — Facturas | `app/compras/facturas/page.tsx` | ✅ CORE |
| Compras — CxP | `app/compras/cxp/page.tsx` | ✅ CORE |
| Ventas — Cotizaciones | `app/ventas/cotizaciones/page.tsx` | ✅ CORE |
| Ventas — Pedidos | `app/ventas/pedidos/page.tsx` | ✅ CORE |
| Ventas — Facturas | `app/ventas/facturas/page.tsx` | ✅ CORE |
| Ventas — CxC | `app/ventas/cxc/page.tsx` | ✅ CORE |
| Finanzas — Cuentas | `app/finanzas/cuentas/page.tsx` | ✅ CORE |
| Finanzas — Movimientos | `app/finanzas/movimientos/page.tsx` | ✅ CORE |
| Usuarios | `app/usuarios/page.tsx` | ✅ CORE |
| Roles | `app/roles/page.tsx` | ✅ CORE |
| Auditoría | `app/auditoria/page.tsx` | ✅ CORE |

---

## TABLAS CUSTOM — APLICACIÓN MANUAL

| Módulo | Archivo | Estado |
|--------|---------|--------|
| Finanzas — Pagos | `app/finanzas/pagos/page.tsx` | ✅ MANUAL |
| Finanzas — Transferencias | `app/finanzas/transferencias/page.tsx` | ✅ MANUAL |
| Contabilidad — Diario | `app/contabilidad/diario/page.tsx` | ✅ MANUAL |
| Contabilidad — Plan de cuentas | `app/contabilidad/plan-cuentas/page.tsx` | ✅ MANUAL (jerarquía visual preservada) |
| Contabilidad — Períodos | `app/contabilidad/periodos/page.tsx` | ✅ MANUAL |

---

## EXCEPCIONES DOCUMENTADAS

| Tabla | Razón | Decisión |
|-------|-------|----------|
| Líneas de asiento (modal en Diario) | Vista de detalle contable dentro de dialog; no es lista de registros navegables. Tiene columnas Débito/Crédito de doble entrada. | ⚠️ EXCEPCIÓN — tabla padre SÍ tiene N.º |
| Dashboard | Sin tablas actualmente. | ⏭ PENDIENTE al construir |
| Reclutamiento / Reportes / IA / Configuración | Páginas stub, sin tablas. | ⏭ PENDIENTE al construir |
| Perfil empleado (`empleados/[id]`) | Vista de detalle individual, no listado. | ⚠️ EXCEPCIÓN — no aplica |

---

## PAGINACIÓN

- **Server-side (35 tablas CORE):** `(page-1)*per_page + rowIndex + 1`. Página 2 con 15 items → comienza en 16.
- **Client-side (tablas custom):** `rowIndex + 1`. Con búsqueda/filtro numera desde 1 los resultados actuales.

---

## REGLA PARA NUEVAS VERTICALES

Cualquier nueva vertical que use `ModuleTablePage`/`DataTable` hereda N.º automáticamente.
Las tablas custom deben agregar N.º explícitamente (ver patrón en tablas manuales).
