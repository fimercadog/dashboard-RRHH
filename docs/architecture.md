# ARCHITECTURE.md — Arquitectura actualizada
> Reemplaza el `docs/architecture.md` antiguo (que describía solo el estado inicial HRMS).
> Actualizado: 2026-10-04.

## Visión general

ERP empresarial modular construido como monorepo:
- `backend/` → Laravel 12 · API REST · SQLite/MySQL · Sanctum Bearer · Spatie Permission
- `frontend/` → Next.js 16 · App Router · TypeScript · TailwindCSS v4 · shadcn/ui

Arquitectura multitenant por `company_id` en todas las tablas operativas.

---

## Módulos activos

```
RRHH          → empleados, departamentos, cargos, asistencia, vacaciones,
                permisos, incapacidades, documentos, turnos
CRM           → leads, clientes, contactos, oportunidades, actividades, segmentos
Inventario    → productos, categorías, marcas, unidades, bodegas, stock, movimientos
Compras       → (Fase C — en construcción) proveedores, OC, recepciones, facturas
Ventas        → (Fase D — pendiente)
Finanzas      → (Fase E — pendiente)
Contabilidad  → (Fase F — pendiente)
```

---

## Capa backend

### Patrón de controlador

```
BaseCrudController
  └── index()  → TableQueryService::apply() → paginación backend
  └── store()  → validatedInput() → model::create() → AuditService::record()
  └── show()
  └── update() → AuditService::record(oldValues)
  └── destroy() → AuditService::record()
```

Todo controller de módulo extiende `BaseCrudController` declarando:
- `$model` — clase Eloquent
- `$resource` — clase API Resource
- `$with` — relaciones eager
- `$searchable` — columnas para búsqueda full-text
- `$filterable` — columnas para filtro exacto

### Servicios core

| Servicio | Responsabilidad |
|----------|----------------|
| `TableQueryService` | Aplica search/filter/sort/paginate a queries Eloquent |
| `AuditService` | Escribe en `audit_logs` con diff de valores |
| `StockService` | **Único escritor** de `stock_movements` y `product_stock` |

### Autenticación
Sanctum Bearer token. `/api/auth/login` devuelve token. `/api/auth/me` devuelve usuario + permisos.

### Autorización
Spatie `laravel-permission`. Permisos por módulo. Gates/Policies por recurso: parciales (ver KNOWN_ISSUES K1).

---

## Capa frontend

### Shell privado (`/app`)
`admin-shell.tsx` — nav filtrado por permisos, sidebar, topbar.

### Patrón de página de módulo
```
page.tsx
  └── ModuleTablePage
        ├── DataTable (TanStack Table · búsqueda · paginación · export)
        ├── CrudModal (create/edit con CrudField[])
        └── ContingencyBanner (si modo offline activo)
```

### Hooks core
- `useApiTable` — state de tabla (data/search/page/loading/error/refresh)
- `useContingency` — estado del modo offline y cola local

---

## Integración de módulos (C–F)

Ver `docs/arquitectura-fases-c-f.md` para el diseño completo. Resumen:

```
CRM (Deal) → Ventas → StockService::exit → Inventario
Compras → StockService::entry → Inventario
Ventas/Compras → Finanzas (CxC/CxP) → Contabilidad (asientos)
```

Ganchos de integración ya en BD (null hasta Fase F):
- `stock_movements.journal_entry_id / posted_at`
- `products.inventory_account_code / cogs_account_code / sale_account_code`

---

## Despliegue

- Backend: Hostinger (SSH · MySQL · PHP 8.4 handler · Bearer auth en CORS)
- Frontend: Node.js standalone o Vercel
- Variables críticas: `APP_URL`, `FRONTEND_URL`, `DB_CONNECTION`, `NEXT_PUBLIC_API_URL`
