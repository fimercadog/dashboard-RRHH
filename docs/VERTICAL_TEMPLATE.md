# VERTICAL_TEMPLATE.md — Plantilla para verticales nuevas

> Usar este archivo como checklist antes de escribir la primera línea de código
> de cualquier vertical nueva (Veterinaria, Inmobiliaria, Nómina, Car Wash, etc.).
>
> El objetivo es **reutilizar todo lo que ya existe en el CORE** y no redescubrir
> problemas que ya fueron resueltos.

---

## 1. Antes de empezar: inspeccionar el CORE

Leer en orden antes de diseñar la vertical:

1. `docs/CORE_CONTRACT.md` — qué existe y no se toca
2. `docs/UX_FORM_RULES.md` — reglas de formularios heredables (**leer siempre**)
3. `docs/DEVELOPMENT_WORKFLOW.md` — orden de capas obligatorio
4. `docs/DEVELOPMENT_MEMORY.md` — lecciones pagadas, no repetir
5. `docs/VERTICALS.md` — ver la vertical más parecida como referencia

---

## 2. Inventario CORE disponible desde día 1 (no reconstruir)

### Backend
| Artefacto | Qué provee |
|-----------|-----------|
| `BaseCrudController` | CRUD completo: index, store, show, update, destroy + paginación + search |
| `ResolvesCompany` | Multitenancy automático por `company_id` |
| `TableQueryService` | Search/filter/sort/paginate sobre cualquier query |
| `AuditService` | Log de acciones (ya integrado en BaseCrudController) |
| `StockService` | Movimientos de stock (si la vertical maneja inventario físico) |
| `PaymentService` | Transacciones financieras (si la vertical procesa pagos) |
| `PurchaseService` / `SaleService` | Documentos de compra/venta con líneas |
| `PendingService` / `PendingProvider` | Ítems pendientes en el header |
| `CampaignController` | Comunicaciones masivas |

### Frontend
| Artefacto | Qué provee |
|-----------|-----------|
| `ModuleTablePage` | Página de módulo completa: tabla + CRUD + contingencia |
| `DataTable` | Tabla con search/paginate/export CSV-PDF |
| `CrudModal` | Modal genérico de creación/edición |
| `useApiTable` | Hook de state de tabla |
| `admin-shell.tsx` | Layout del panel: sidebar + header + auth guard |
| `SaleLineItemsEditor` / `PurchaseLineItemsEditor` | Tablas de ítems de documentos |
| Custom Dialog (patrón Compras/Ventas) | Formularios con selectores FK cargados desde API |

### Auth
- Sanctum Bearer token, login producción + demo
- Roles base: Super Admin, Administrador, Empleado
- Filtrado de nav por permisos

---

## 3. Orden obligatorio de implementación

> **Regla permanente (M28): no se toca una capa sobre una BD provisional.**
> Si la BD está en auditoría → NO avanzar a Backend.
> Si el Backend no está alineado con la BD → NO avanzar a Frontend.

```
FASE 1 — CONTRATO DE DATOS (sign-off antes de código)
  ├── Diseñar tablas y relaciones
  ├── Verificar que ningún campo obligará al usuario a introducir IDs técnicos (UX-001)
  ├── Confirmar company_id en todas las tablas operativas
  ├── Definir estados y sus transiciones
  └── SIGN OFF explícito de Fidel antes de continuar

FASE 2 — BASE DE DATOS
  ├── Migración de permisos: add_{vertical}_permissions.php
  ├── Migraciones de tablas (company_id, FKs, índices, constraints)
  └── php artisan migrate:fresh --seed pasa sin errores

FASE 3 — BACKEND (Backend Alignment Pass)
  ├── Modelos (fillable, casts, relaciones, scopes)
  ├── Services (si hay lógica compleja o integración con StockService/PaymentService)
  ├── Resources JSON
  ├── FormRequests (ownedExists() para FKs company-scoped — ver M21)
  ├── Controllers (BaseCrudController o custom justificado)
  ├── Rutas en api.php
  └── Verificar: BD real ↔ Models ↔ Services ↔ API ↔ validaciones están alineados

FASE 4 — FRONTEND
  ├── Leer UX_FORM_RULES.md — aplicar checklist completo
  ├── Ningún campo FK como type="number"
  ├── Ningún campo de archivo como type="text"
  ├── Selectores de FK cargan desde la API al abrir modal
  ├── Estados con defaultValue explícito
  ├── Campos catalogados con select (no texto libre)
  ├── Responsive: funciona en móvil
  └── Link en admin-shell nav

FASE 5 — AUTH
  ├── Migración de permisos está aplicada
  ├── Roles sembrados
  └── Nav filtrado por permisos correctamente

FASE 6 — E2E Y REGRESIÓN
  ├── Playwright: flujos críticos (crear, editar, flujo de negocio principal)
  ├── php artisan test — 0 errores
  └── Recorrer módulos existentes: nada roto
```

---

## 4. Protocolo de clasificación CORE vs. vertical

**Ejecutar antes de cerrar CUALQUIER tarea:**

```
CAMBIO REALIZADO:
  _______________________________________________

CLASIFICACIÓN:
  [ ] Específico de esta vertical únicamente
  [ ] CORE reutilizable (aplica a ≥2 verticales o a todas las futuras)

SI ES CORE — actualizar estos archivos:
  [ ] docs/CORE_CONTRACT.md     (si modifica un contrato inamovible)
  [ ] docs/UX_FORM_RULES.md     (si es una regla de formularios/UX)
  [ ] docs/DEVELOPMENT_MEMORY.md (nuevo aprendizaje M##)
  [ ] docs/VERTICALS.md          (si cambia el inventario CORE disponible)
  [ ] docs/VERTICAL_TEMPLATE.md  (este archivo — si el template debe cambiar)

VERTICALES AFECTADAS QUE DEBEN ACTUALIZARSE:
  [ ] RRHH   [ ] CRM   [ ] Inventario   [ ] Compras
  [ ] Ventas [ ] Finanzas [ ] Contabilidad
  [ ] Todas las futuras
```

**Regla:** La corrección no muere en el commit donde fue hecha.
Si es CORE, debe propagarse antes de declarar DONE.

---

## 5. Definition of Done para una vertical completa

### Datos
- [ ] Todas las tablas tienen `company_id`
- [ ] `php artisan migrate:fresh --seed` pasa sin errores
- [ ] Los datos de demo son coherentes y representativos

### Backend
- [ ] Todos los models tienen fillable/casts/relaciones correctos
- [ ] Todos los endpoints respetan `company_id` scope
- [ ] FormRequests validan FKs con `ownedExists()` (no `exists:TABLE,id`)
- [ ] Los servicios singulares son los únicos escritores de sus entidades
- [ ] `php artisan test` — 0 errores

### Frontend (checklist UX completo)
- [ ] Ningún campo visible expone un ID técnico
- [ ] Ningún campo solicita ruta de servidor para archivos
- [ ] Todos los selectores de FK cargan desde la API
- [ ] Todos los estados tienen defaultValue
- [ ] Todos los campos catalogados tienen opciones fijas
- [ ] La UI funciona en desktop y móvil sin overflow
- [ ] `npm run build` — 0 errores TypeScript

### Auth
- [ ] Migración de permisos aplicada
- [ ] Nav filtrado correctamente por permisos

### Regresión
- [ ] Ningún módulo existente dejó de funcionar
- [ ] `php artisan migrate:fresh --seed` sigue pasando

### E2E (requerido antes de despliegue a cliente real)
- [ ] Playwright cubre los flujos críticos
- [ ] 0 tests en cuarentena sin resolver

---

## 6. Referencia de verticales como base

| Vertical | Patrón de referencia para... |
|----------|------------------------------|
| RRHH | Solicitudes con aprobación, Employee ↔ User |
| CRM | Entidades con relaciones opcionales, pipeline stages |
| Inventario | StockService como único escritor de stock |
| Compras | Documentos con líneas, PurchaseService, Custom Dialog |
| Ventas | cotización → pedido → factura, SaleService |
| Finanzas | PaymentService como único escritor de saldos |
| Contabilidad | Hooks de integración, AccountingService (Fase F) |
