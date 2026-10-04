# KNOWN_ISSUES.md — Problemas conocidos y deudas técnicas

## Brechas de implementación actuales

### K1 — Policies/gates por recurso ausentes
**Gravedad:** Media
**Estado:** Abierto
**Detalle:** Existen permisos Spatie (employees.manage, etc.) pero no hay `Policy` Laravel por modelo. Los middlewares de ruta verifican el permiso global, pero no hay autorización fina (¿puede este usuario editar ESTE empleado?).
**Impacto:** Cualquier usuario con `employees.manage` puede editar cualquier empleado de su empresa.

### K2 — Perfil de empleado incompleto
**Gravedad:** Baja (demo)
**Estado:** Parcialmente resuelto (Fase 0)
**Detalle:** La página `/app/empleados/[id]` fue creada (no existía — bug activo). Muestra datos personales, laborales, contacto de emergencia y notas. EmployeeResource actualizado para exponer gender, address, city, emergency_contact_name, emergency_contact_phone, notes. Pendiente: tabs de asistencia, documentos, vacaciones (Fase 10).

### K3 — Formularios públicos no envían datos
**Gravedad:** Baja (demo)
**Estado:** Abierto
**Detalle:** El formulario de contacto en el sitio público es visual. No tiene endpoint backend ni envío real.

### K4 — Sin tests automatizados
**Gravedad:** Alta (para producción)
**Estado:** Abierto
**Detalle:** No existe ningún test: ni unitario, ni de integración, ni E2E.
**Impacto:** Cambios pueden romper módulos existentes sin que haya red de seguridad.

### K5 — api.md y database.md desactualizados
**Gravedad:** Media (documentación)
**Estado:** Abierto
**Detalle:** `docs/api.md` solo lista endpoints HRMS originales. `docs/database.md` solo lista tablas HRMS. Ambos ignoran CRM e Inventario.
**Impacto:** Quien lee la documentación tiene una imagen falsa del sistema.

### K6 — IA sin proveedor conectado
**Gravedad:** Baja
**Estado:** Abierto
**Detalle:** La interfaz del asistente de IA está lista pero no tiene LLM conectado.

### K7 — Contingencia: no todos los módulos registrados explícitamente
**Gravedad:** Baja
**Estado:** Abierto
**Detalle:** `ModuleTablePage` soporta contingencia, pero la lista de módulos habilitados para encolar en contingencia puede no incluir los módulos de CRM e Inventario añadidos después.

### K8 — Fase C (Compras) completa, sin E2E
**Gravedad:** Baja
**Estado:** Parcialmente resuelto
**Detalle:** Todas las capas de Compras implementadas y con 9 tests pasando. Pendiente: E2E Playwright para flujo de creación de orden → recepción → posteo → factura → CxP. Orden/Recepción/Factura son read-only en frontend (sin formulario CRUD completo); Proveedores sí tiene CRUD completo.

### K10 — UI de Ventas: sin formulario para documentos con líneas
**Gravedad:** Media (producto — no bloqueante para demo)
**Estado:** Deuda UX abierta
**Detalle:** Las páginas de Cotizaciones, Pedidos de Venta y Facturas de Venta muestran la lista pero no tienen formulario de creación en la interfaz. Igual que K9 para Compras: requiere wizard multi-línea.
**Impacto:** El usuario no puede crear Cotización/Pedido/Factura desde la UI en la demo actual. Puede hacerlo vía API directa.

### K9 — UI de Compras: sin formulario para documentos con líneas
**Gravedad:** Media (producto — no bloqueante para demo)
**Estado:** Deuda UX abierta
**Detalle:** Las páginas de Órdenes de Compra, Recepciones y Facturas de Compra muestran la lista pero no tienen formulario de creación en la interfaz. La creación de estos documentos requiere líneas de productos (quantity, unit_cost por producto), lo que necesita un wizard multi-línea. Proveedores sí tiene CRUD completo.
**Impacto:** El usuario no puede crear OC/Recepción/Factura desde la UI en la demo actual. Puede hacerlo vía API directa.
**Pendiente:** Wizard/formulario con tabla de ítems editable para: Orden de Compra → Recepción → Factura de Compra. Incluir: selector de proveedor, selector de bodega, fecha, líneas (producto + cantidad + precio unitario), totales automáticos, botón "Postear".

---

## Deudas técnicas conocidas y aceptadas

| Deuda | Por qué se acepta | Cuándo resolver |
|-------|-------------------|-----------------|
| Sin tests | Demo en construcción | Antes de primera venta/despliegue real |
| SQLite en producción demo | Suficiente para demos | Ya migrado a MySQL en Hostinger |
| FormRequests no en todos los métodos | BaseCrudController hace fallback a `$request->all()` | Completar cuando haya tiempo |
| Sin pantallas 403 por ruta | Frontend filtra nav; no hay rutas profundas públicas | Antes de primer cliente real |
| Reportes: catálogo visual, no tabulares completos | Demo | Backlog priorizado |
| UI Compras sin wizard de líneas | Demo funciona con API directa; wizard requiere diseño propio | Antes de primer cliente real |
