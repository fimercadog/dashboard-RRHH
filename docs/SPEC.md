# SPEC — Matriz Maestra de Reutilización FidelOS

> **REFERENCIA OBLIGATORIA antes de comenzar cualquier nueva vertical.**
> No auditar FidelOS desde cero. Comparar la vertical nueva contra esta matriz y obtener el plan directamente.

**Leyenda de acciones:**
| Símbolo | Significado |
|---------|-------------|
| 🟢 REUTILIZAR | Ya existe, funciona, se hereda sin cambios |
| 🟡 ADAPTAR | Existe pero requiere cambios específicos para la vertical |
| 🔴 DESARROLLAR | No existe, la vertical lo necesita desde cero |
| ⚪ NO NECESARIO | La vertical no lo requiere |
| ⚠️ VERIFICAR | Documentado pero requiere confirmación en código/tests |

**Stack base confirmado:** Laravel 11 · Next.js 15 (static export) · MySQL/SQLite · Sanctum Bearer · Spatie RBAC · Hostinger (CloudLinux PHP 8.4)

---

## A. CORE TRANSVERSAL

### A1. Autenticación y sesión

| Capacidad | Componente | Estado | Acción nueva vertical | Adaptar | Dependencias | Tests |
|-----------|-----------|--------|-----------------------|---------|-------------|-------|
| Login con email/password | `AuthController`, `/api/auth/login` | ✅ Implementado | 🟢 REUTILIZAR | Nada | Sanctum | ⚠️ Verificar |
| Logout | `AuthController`, `/api/auth/logout` | ✅ Implementado | 🟢 REUTILIZAR | Nada | Sanctum | ⚠️ Verificar |
| Validación de sesión activa | `AuthController`, `/api/auth/me` → user + permisos | ✅ Implementado | 🟢 REUTILIZAR | Nada | Sanctum | ⚠️ Verificar |
| Bearer token en frontend | `lib/api.ts` + interceptor 401 | ✅ Implementado | 🟢 REUTILIZAR | Nada | axios | — |
| Login demo con selector de roles | `demo-login.ts` + `DEMO_USERS` + 4 agentes precargados | ✅ Implementado | 🟡 ADAPTAR | Cambiar emails/contraseñas demo y roles del selector | `NEXT_PUBLIC_DEMO_PASSWORD` | — |
| Guard de ruta frontend | `middleware.ts` protege `/app/*` | ✅ Implementado | 🟢 REUTILIZAR | Nada | next/server | — |
| Redirección por sesión expirada | Evento `AUTH_EXPIRED_EVENT`, interceptor en `api.ts` | ✅ Implementado | 🟢 REUTILIZAR | Nada | — | — |

### A2. Multitenancy

| Capacidad | Componente | Estado | Acción | Adaptar | Dependencias | Tests |
|-----------|-----------|--------|--------|---------|-------------|-------|
| Scope automático por empresa | `ResolvesCompany` concern; todo controller lo hereda | ✅ Implementado | 🟢 REUTILIZAR | Ninguno — automático al extender BaseCrudController | `companies` table | ⚠️ Verificar |
| `company_id` en toda tabla operativa | Convención de migración + `CORE_CONTRACT.md` §3 | ✅ Implementado | 🟢 REUTILIZAR | Toda tabla nueva de la vertical debe incluirlo | — | — |
| FK cross-company bloqueada | `ownedExists()` en `ApiFormRequest` | ✅ Implementado | 🟢 REUTILIZAR | Usar en todo FormRequest con FK | — | — |

### A3. RBAC y permisos

| Capacidad | Componente | Estado | Acción | Adaptar | Dependencias | Tests |
|-----------|-----------|--------|--------|---------|-------------|-------|
| Roles base | Spatie; 5 roles: Super Admin · Admin empresa · RRHH · Supervisor · Empleado | ✅ Implementado | 🟢 REUTILIZAR | Nada — los roles ya existen | Spatie laravel-permission | ⚠️ Verificar |
| Permisos granulares | Declarados en migraciones `add_{vertical}_permissions.php` | ✅ Implementado | 🟡 ADAPTAR | Nueva migración con los permisos de la vertical | Spatie | ⚠️ Verificar |
| Filtro de navegación por permisos | `hasAnyPermission()` en `admin-shell.tsx` y `navGroups` | ✅ Implementado | 🟢 REUTILIZAR | Agregar permisos de la vertical al navGroup | — | — |
| Guard de ruta por permiso | `AdminShell` — guard por `activeNav.permissions` | ✅ Implementado | 🟢 REUTILIZAR | Definir `permissions` en cada `NavItem` de la vertical | — | — |
| Delete granular por recurso | Migraciones RBAC por módulo | ✅ Implementado | 🟡 ADAPTAR | Declarar permisos delete de la vertical | Spatie | — |

### A4. Auditoría

| Capacidad | Componente | Estado | Acción | Adaptar | Dependencias | Tests |
|-----------|-----------|--------|--------|---------|-------------|-------|
| Log automático de CRUD | `AuditService`; llamado en BaseCrudController | ✅ Implementado | 🟢 REUTILIZAR | Ninguno — automático | `audit_logs` table | — |
| Log manual en controllers especiales | `$audit->record(...)` directamente | ✅ Implementado | 🟢 REUTILIZAR | Llamar en lógica de negocio especial | AuditService | — |
| Vista de auditoría en frontend | `/app/auditoria` con DataTable | ✅ Implementado | 🟢 REUTILIZAR | Nada | — | — |

---

## B. MÓDULOS ERP REUTILIZABLES

### B1. CRUD genérico

| Capacidad | Componente | Estado | Acción | Adaptar | Dependencias | Tests |
|-----------|-----------|--------|--------|---------|-------------|-------|
| `BaseCrudController` | `index` / `store` / `show` / `update` / `destroy`; auto-detección FormRequest | ✅ Implementado | 🟢 REUTILIZAR | Declarar `$model`, `$resource`, `$with`, `$searchable`, `$filterable` | — | ⚠️ Verificar |
| `TableQueryService` | search · filter · sort · paginate; `DEFAULT_PER_PAGE=10`, `MAX_PER_PAGE=100` | ✅ Implementado | 🟢 REUTILIZAR | Nada | — | — |
| Paginación con límite seguro | `min($perPage, TableQueryService::MAX_PER_PAGE)` en BaseCrudController | ✅ Implementado | 🟢 REUTILIZAR | Nada | — | — |
| Resources JSON | Patrón `app/Http/Resources/` | ✅ Implementado | 🟡 ADAPTAR | Crear Resource por entidad | — | — |
| FormRequest + validación | Patrón `StoreXxxRequest` extendiendo `ApiFormRequest` | ✅ Implementado | 🟡 ADAPTAR | Definir reglas por entidad | ApiFormRequest | ⚠️ Verificar |

### B2. Clientes y CRM

| Módulo | Componente | Estado | Acción | Adaptar | Tests |
|--------|-----------|--------|--------|---------|-------|
| Clientes (CRUD completo) | `ClientController` + `ClientResource` + pagina frontend | ✅ Implementado | 🟡 ADAPTAR | Campos sectoriales específicos | ⚠️ Verificar |
| Contactos | `ContactController` + relación a cliente | ✅ Implementado | 🟡 ADAPTAR | Relación con la entidad principal de la vertical | ⚠️ Verificar |
| Leads + Wizard captación | Wizard 3 pasos; `LeadController`; lógica de estado | ✅ Implementado | 🟡 ADAPTAR | Copy y campos del formulario de captación | ⚠️ Verificar |
| Oportunidades / Deals | Pipeline con etapas, `DealController` | ✅ Implementado | 🟡 ADAPTAR | Etapas del pipeline del sector | ⚠️ Verificar |
| Actividades | Log de contactos/tareas por deal | ✅ Implementado | 🟡 ADAPTAR | Tipos de actividad del sector | — |
| Segmentos | Agrupación de clientes por criterio | ✅ Implementado | 🟡 ADAPTAR | Criterios de segmentación del sector | — |

### B3. Productos e inventario

| Módulo | Componente | Estado | Acción | Adaptar | Tests |
|--------|-----------|--------|--------|---------|-------|
| Productos (físico/service) | `ProductController` + tipos | ✅ Implementado | 🟡 ADAPTAR | Atributos sectoriales (talla/color/especie/etc.) | ⚠️ Verificar |
| Categorías / Marcas / Unidades | CRUDs de soporte | ✅ Implementado | 🟢 REUTILIZAR | Nada | — |
| Bodegas / Almacenes | `WarehouseController` | ✅ Implementado | 🟢 REUTILIZAR | Nada | — |
| Stock (solo lectura) | Vista de stock por producto/bodega | ✅ Implementado | 🟢 REUTILIZAR | Nada | — |
| `StockService` | entry / exit / adjustment / transfer; único escritor | ✅ Implementado | 🟢 REUTILIZAR | Llamar desde vertical, nunca escribir directo | ⚠️ Verificar |
| Movimientos de inventario | `StockMovementController` | ✅ Implementado | 🟢 REUTILIZAR | Nada | — |

### B4. Compras

| Módulo | Componente | Estado | Acción | Adaptar | Tests |
|--------|-----------|--------|--------|---------|-------|
| Proveedores | `SupplierController` | ✅ Implementado | 🟡 ADAPTAR | Campos sectoriales | ⚠️ Verificar |
| Órdenes de compra | Wizard 6 pasos; `PurchaseOrderController` | ✅ Implementado | 🟡 ADAPTAR | Ítems y flujo del sector | ⚠️ Verificar |
| Recepciones | `PurchaseReceiptController`; dispara StockService.entry | ✅ Implementado | 🟢 REUTILIZAR | Nada | ⚠️ Verificar |
| Facturas de compra | `PurchaseInvoiceController`; dispara CxP | ✅ Implementado | 🟢 REUTILIZAR | Nada | ⚠️ Verificar |
| CxP (Cuentas por Pagar) | `PurchasesPendingProvider`; en tiempo real | ✅ Implementado | 🟢 REUTILIZAR | Nada | — |
| `PurchaseService` | Posteo centralizado compras + efectos inventario/CxP | ✅ Implementado | 🟢 REUTILIZAR | Llamar desde vertical | ⚠️ Verificar |

### B5. Ventas

| Módulo | Componente | Estado | Acción | Adaptar | Tests |
|--------|-----------|--------|--------|---------|-------|
| Cotizaciones | Wizard; `QuoteController` | ✅ Implementado | 🟡 ADAPTAR | Ítems y términos del sector | ⚠️ Verificar |
| Pedidos | `SaleOrderController` | ✅ Implementado | 🟡 ADAPTAR | Flujo del sector | ⚠️ Verificar |
| Facturas de venta | `SaleInvoiceController`; dispara CxC + StockService.exit | ✅ Implementado | 🟢 REUTILIZAR | Nada | ⚠️ Verificar |
| CxC (Cuentas por Cobrar) | `SalesPendingProvider`; en tiempo real | ✅ Implementado | 🟢 REUTILIZAR | Nada | — |
| `SaleService` | Posteo centralizado ventas + efectos inventario/CxC | ✅ Implementado | 🟢 REUTILIZAR | Llamar desde vertical | ⚠️ Verificar |

### B6. Finanzas

| Módulo | Componente | Estado | Acción | Adaptar | Tests |
|--------|-----------|--------|--------|---------|-------|
| Cuentas (CashAccounts) | `CashAccountController` | ✅ Implementado | 🟢 REUTILIZAR | Nada | ⚠️ Verificar |
| Pagos | `PaymentController` | ✅ Implementado | 🟢 REUTILIZAR | Nada | ⚠️ Verificar |
| Transferencias entre cuentas | `TransferController` | ✅ Implementado | 🟢 REUTILIZAR | Nada | ⚠️ Verificar |
| Movimientos financieros | `FinancialTransactionController`; FinancePendingProvider (overdraft) | ✅ Implementado | 🟢 REUTILIZAR | Nada | — |
| `PaymentService` | Único escritor de saldos y transacciones | ✅ Implementado | 🟢 REUTILIZAR | Llamar desde vertical | ⚠️ Verificar |

### B7. Contabilidad

| Módulo | Componente | Estado | Acción | Adaptar | Tests |
|--------|-----------|--------|--------|---------|-------|
| Plan de Cuentas | `ChartOfAccountController` | ✅ Implementado | 🟡 ADAPTAR | Plan de cuentas del sector | ⚠️ Verificar |
| Períodos contables | `AccountingPeriodController` | ✅ Implementado | 🟢 REUTILIZAR | Nada | — |
| Libro Diario | `JournalEntryController` | ✅ Implementado | 🟢 REUTILIZAR | Nada | — |
| Configuración contable | Configuración de cuentas por defecto | ✅ Implementado | 🟡 ADAPTAR | Cuentas por defecto del sector | — |
| `AccountingService` | Único escritor de asientos contables | ✅ Implementado | 🟢 REUTILIZAR | Llamar desde vertical | ⚠️ Verificar |

### B8. RRHH

| Módulo | Componente | Estado | Acción | Adaptar | Tests |
|--------|-----------|--------|--------|---------|-------|
| Empleados | `EmployeeController`; estados; avatar | ✅ Implementado | 🟡 ADAPTAR | Si la vertical no tiene empleados directos, puede ser ⚪ | ⚠️ Verificar |
| Asistencia | `AttendanceController` | ✅ Implementado | ⚪ / 🟡 | Solo si la vertical gestiona personal | — |
| Vacaciones / Permisos / Incapacidades | Controllers de solicitudes + aprobación | ✅ Implementado | ⚪ / 🟡 | Solo si gestiona personal | — |
| Documentos de empleado | Upload real + `DocumentController` | ✅ Implementado | ⚪ / 🟡 | Solo si gestiona personal | — |
| Turnos | `ShiftController` | ✅ Implementado | ⚪ / 🟡 | Solo si gestiona turnos | — |
| Reclutamiento (vacantes/candidatos/entrevistas) | `JobVacancyController` + `JobCandidateController` + `JobApplicationController` + `JobInterviewController` (4 controllers separados) | ✅ Implementado | ⚪ / 🟡 | Solo si la vertical contrata personal | — |

### B9. Dashboard y reportes

| Módulo | Componente | Estado | Acción | Adaptar | Tests |
|--------|-----------|--------|--------|---------|-------|
| Dashboard con KPIs | `DashboardController`; tarjetas + gráficos | ✅ Implementado | 🟡 ADAPTAR | KPIs e indicadores del sector | — |
| Cumpleaños próximos | `DashboardController::upcomingBirthdays()` (N+1 eliminado) | ✅ Implementado | ⚪ / 🟡 | Solo si la vertical gestiona personas | — |
| Reportes generales | `/app/reportes` vista genérica | ✅ Implementado | 🟡 ADAPTAR | Reportes específicos del sector | — |

### B10. Administración y configuración

| Módulo | Componente | Estado | Acción | Adaptar | Tests |
|--------|-----------|--------|--------|---------|-------|
| Organización / empresa | `CompanyController` | ✅ Implementado | 🟢 REUTILIZAR | Nada | — |
| Usuarios | `UserController` + avatar | ✅ Implementado | 🟢 REUTILIZAR | Nada | ⚠️ Verificar |
| Roles y permisos | Spatie + `RoleController` | ✅ Implementado | 🟡 ADAPTAR | Permisos de la vertical | ⚠️ Verificar |
| Configuración general | `ConfigController` | ✅ Implementado | 🟡 ADAPTAR | Configuraciones propias de la vertical | — |

---

## C. FUNCIONALIDADES ESPECÍFICAS DE VERTICAL

> Estas son propias de dfctalentohumano y **no se heredan** a otras verticales sin evaluación explícita.

| Funcionalidad | Vertical origen | Acción en nueva vertical |
|---------------|----------------|--------------------------|
| Gestión de asistencia (marcaciones, turnos) | dfctalentohumano (RRHH) | ⚪ Evaluar según necesidad |
| Incapacidades y licencias | dfctalentohumano (RRHH) | ⚪ Evaluar |
| Reclutamiento (vacantes, candidatos, entrevistas) | dfctalentohumano (RRHH) | ⚪ Evaluar |
| IA para RRHH (premium, diferido) | dfctalentohumano (RRHH) | ⚪ No aplicable en general |
| Segmentos de clientes por criterio RRHH | dfctalentohumano (CRM) | ⚪ Adaptar criterios |

---

## D. INFRAESTRUCTURA

| Capacidad | Componente | Estado | Acción nueva vertical | Adaptar | Dependencias |
|-----------|-----------|--------|-----------------------|---------|-------------|
| Deploy backend SSH | Script paramiko: git pull + composer + migrate --force + optimize | ✅ Implementado | 🟡 ADAPTAR | Cambiar `REMOTE` path; mismos comandos | paramiko, PHP 8.4 |
| Deploy frontend SFTP | Script paramiko: sube `out/` completo → `public_html/` | ✅ Implementado | 🟡 ADAPTAR | Cambiar `LOCAL_DIR` y `REMOTE_DIR` | paramiko |
| PHP 8.4 Hostinger | Handler: `/opt/alt/php84/usr/bin/php` (CloudLinux) | ✅ Implementado | 🟢 REUTILIZAR | Mismo handler si mismo servidor | Hostinger |
| Static export Next.js | `output:"export"`, `trailingSlash:true`, `images:{unoptimized:true}` | ✅ Implementado | 🟢 REUTILIZAR | Misma config | next.config.ts |
| Cache CDN | `Cache-Control: must-revalidate` en HTML | ✅ Implementado | 🟢 REUTILIZAR | Misma config | next.config.ts |
| Variables de entorno | `NEXT_PUBLIC_API_URL`, `NEXT_PUBLIC_DEMO_PASSWORD` | ✅ Implementado | 🟡 ADAPTAR | Nuevos valores por vertical/dominio | `.env.local` |
| `--force` en Artisan en producción | Regla documentada en `docs/MEMORY.md` | ✅ Implementado | 🟢 REUTILIZAR | Ya en script de deploy | — |
| `storage:link` obligatorio | Requerido para upload de archivos | ✅ Implementado | 🟢 REUTILIZAR | Ejecutar una vez al desplegar | artisan |
| Landing/web comercial | `frontend/src/app/` fuera de `/app/app/`: home, blog, contacto, demo, nosotros, precios, privacidad, producto, reclutamiento, soluciones, terminos | ✅ Implementado | 🟡 ADAPTAR | Reemplazar copy, imágenes y datos de la vertical | next.config.ts |

---

## E. UX / UI

### E1. Layout y navegación

| Capacidad | Componente | Estado | Acción | Adaptar |
|-----------|-----------|--------|--------|---------|
| `AdminShell` — layout único del panel | sidebar + header + auth guard + dark/light + mobile | ✅ Implementado | 🟡 ADAPTAR | Agregar `navGroups` de la vertical |
| Sidebar con grupos de navegación | Array `navGroups` en `AdminShell` | ✅ Implementado | 🟡 ADAPTAR | Definir ítems, íconos, rutas y permisos |
| Header con botones CORE | BetaNotice · PendingButton · CommunicationsButton · Feedback · Tema · Logout | ✅ Implementado | 🟢 REUTILIZAR | Nada |
| Trailing slash normalization | `.replace(/\/$/, "")` en active state | ✅ Implementado | 🟢 REUTILIZAR | Nada |
| Responsive / mobile nav | Drawer móvil en `AdminShell` | ✅ Implementado | 🟢 REUTILIZAR | Nada |
| Dark / light mode | `ThemeProvider` + tokens CSS en `globals.css` | ✅ Implementado | 🟢 REUTILIZAR | Nada |

### E2. Tablas

| Capacidad | Componente | Estado | Acción | Adaptar |
|-----------|-----------|--------|--------|---------|
| `DataTable` TanStack Table | search · paginación backend · sort · loading · error · vacío | ✅ Implementado | 🟢 REUTILIZAR | Definir columnas |
| Export CSV | `DataTable` | ✅ Implementado | 🟢 REUTILIZAR | Automático |
| Export PDF | `DataTable` → `GET /api/exports/{resource}.pdf` → `ExportController` (barryvdh/laravel-dompdf, `resources/views/exports/table.blade.php`, límite 5 000 filas, scope multiempresa, permiso por recurso) | ✅ Implementado | 🟢 REUTILIZAR | Agregar el `resource` y su permiso en `$map`/`$permissionByResource` de `ExportController` para cada tabla nueva |
| Zebra (fila alterna) | `DataTable` | ✅ Implementado | 🟢 REUTILIZAR | Automático |
| Numeración `#` | Columna global en `DataTable` (UX-006) | ✅ Implementado | 🟢 REUTILIZAR | Automático |
| `ModuleTablePage` | Wrapper estándar tabla+CRUD | ✅ Implementado | 🟢 REUTILIZAR | Pasar `columns`, `fields`, `endpoint`, `title` |

### E3. Formularios

| Capacidad | Componente | Estado | Acción | Adaptar |
|-----------|-----------|--------|--------|---------|
| `CrudModal` genérico | Modal por `CrudField[]`; tipos: text · select · relation-select · date · file · number · textarea | ✅ Implementado | 🟢 REUTILIZAR | Definir `CrudField[]` |
| Selectores FK | `CrudField type:"relation-select"` → endpoint + labelKey | ✅ Implementado | 🟢 REUTILIZAR | Definir endpoint del selector |
| Upload de archivos | `CrudField type:"file"` → upload real, ruta en BD, URL via Resource | ✅ Implementado | 🟢 REUTILIZAR | Definir mimes aceptados |
| Selects de valores finitos | `CrudField type:"select"` con opciones fijas | ✅ Implementado | 🟢 REUTILIZAR | Definir opciones |
| Wizard multi-paso | Implementado en Leads (3 pasos), Compras (6 pasos), Ventas | ✅ Implementado | 🟡 ADAPTAR | Definir pasos, validaciones y lógica |
| `useApiTable` hook | Estado completo de tabla (data, search, page, loading, refresh) | ✅ Implementado | 🟢 REUTILIZAR | Nada |
| Endpoint `/selector` sin gate | `GET /{entidad}/selector` → solo `[id, label]` | ✅ Implementado | 🟢 REUTILIZAR | Crear uno por entidad FK |

### E4. Componentes visuales

| Capacidad | Componente | Estado | Acción | Adaptar |
|-----------|-----------|--------|--------|---------|
| `Badge` | Bootstrap 5 exact colors · 8 variantes · `whitespace-nowrap` | ✅ Implementado | 🟢 REUTILIZAR | Nada |
| `badgeVariant(status)` | Mapeo 50+ valores → variante semántica | ✅ Implementado | 🟡 ADAPTAR | Agregar estados propios al `VARIANT_MAP` |
| `badgeLabel(status)` | 40+ labels en español | ✅ Implementado | 🟡 ADAPTAR | Agregar labels de la vertical |
| Avatar con fallback iniciales | Upload real + preview + iniciales (UX-007) | ✅ Implementado | 🟡 ADAPTAR | Cambiar entidad objetivo |
| BetaNotice | Banner de beta en header | ✅ Implementado | 🟢 REUTILIZAR | Nada |
| ContingencyBanner | Banner modo offline | ✅ Implementado | 🟢 REUTILIZAR | Nada |
| LogoMark + branding | Identidad visual | ✅ Implementado | 🟡 ADAPTAR | Logo e identidad de la vertical |

### E5. Accesibilidad y inputs avanzados

| Capacidad | Componente | Estado | Acción | Adaptar |
|-----------|-----------|--------|--------|---------|
| Dictado de voz en formularios | `useSpeechInput` + `VoiceInputWrapper`; `es-CO` → `es-ES` fallback | ✅ Implementado | 🟢 REUTILIZAR | Agregar `voice:true` al `CrudField` deseado |
| `Input` con forwardRef | `input.tsx` — `React.forwardRef` para inyección de ref | ✅ Implementado | 🟢 REUTILIZAR | Nada |
| Login demo con selector visual | 4 agentes + autoLogin | ✅ Implementado | 🟡 ADAPTAR | Cambiar usuarios demo |

---

## F. SEGURIDAD / RBAC

| Capacidad | Componente | Estado | Acción | Adaptar |
|-----------|-----------|--------|--------|---------|
| Cabeceras HTTP de seguridad | HSTS · X-Frame-Options · X-Content-Type · Referrer-Policy | ✅ Implementado | 🟢 REUTILIZAR | Nada |
| CSP parcial | `frame-ancestors 'none'` · `object-src 'none'` · `base-uri 'self'` | ✅ Implementado | 🟢 REUTILIZAR | Nada |
| Permissions-Policy | `microphone=(self)` — habilita voz en producción | ✅ Implementado | 🟢 REUTILIZAR | Nada |
| Rate limiting en auth | `throttle:6,1` en `/auth/login`, `/auth/forgot-password`, `/auth/reset-password`; `/auth/logout` sin throttle | ✅ PARCIAL | 🟢 REUTILIZAR | Aplica solo en rutas de auth; logout no cubierto |
| IDOR bloqueado | `ResolvesCompany` + `ownedExists()` | ✅ Implementado | 🟢 REUTILIZAR | Nada |
| Políticas por modelo | Solo `EmployeePolicy` + `StockMovementPolicy`; resto de modelos sin Policy | ✅ PARCIAL | 🟡 ADAPTAR | Crear Policy para entidades sensibles de la vertical |
| CORS Sanctum | Configurado en `config/cors.php` + env | ✅ Implementado | 🟡 ADAPTAR | Agregar dominio de la vertical |
| Permisos granulares por acción | view · manage · delete separados por módulo | ✅ Implementado | 🟡 ADAPTAR | Migración de permisos por vertical |

---

## G. PERFORMANCE / ESCALABILIDAD

| Capacidad | Componente | Estado | Acción | Observaciones |
|-----------|-----------|--------|--------|---------------|
| Índices compuestos BD | `(company_id, status)` y `(company_id, created_at)` en tablas de alta frecuencia | ✅ Implementado | 🟢 REUTILIZAR | Toda tabla nueva debe incluirlos |
| `DEFAULT_PER_PAGE=10`, `MAX_PER_PAGE=100` | `TableQueryService` | ✅ Implementado | 🟢 REUTILIZAR | Evita queries sin límite |
| N+1 eliminado en birthday query | `DashboardController::upcomingBirthdays()` | ✅ Implementado | 🟢 REUTILIZAR | Patrón a seguir en queries de dashboard |
| Paginación backend | `DataTable` + `useApiTable` + `BaseCrudController` | ✅ Implementado | 🟢 REUTILIZAR | No cargar tablas completas |
| Singleton para petición `/auth/me` | `fetchMe()` en `admin-shell.tsx` — una sola petición compartida | ✅ Implementado | 🟢 REUTILIZAR | Nada |
| `config:cache` · `route:cache` · `view:cache` | Script de deploy | ✅ Implementado | 🟢 REUTILIZAR | Nada |
| Test de 100k+ registros | — | 🔴 No existe | 🔴 DESARROLLAR | Pendiente de pruebas de carga reales |

---

## H. TESTS / QA

| Capacidad | Componente | Estado | Acción | Observaciones |
|-----------|-----------|--------|--------|---------------|
| Test suite Laravel (Feature + Unit) | 21 archivos en `backend/tests/` (19 Feature: Auth, Dashboard, Leads, Stock, Finance, Accounting, Purchases, Sales, RBAC, Contingency, Pending, Communications, Recruitment…) | ✅ Existe — issue pre-existente | 🟢 REUTILIZAR patrón | Algunos tests fallan; auditar antes del release gate |
| Ejecución en SQLite | `DB_CONNECTION=sqlite` en `.env.testing` | ✅ Implementado | 🟢 REUTILIZAR | — |
| Compatibilidad MySQL | Ejecutar suite en MySQL antes de release | ✅ Documentado | 🟢 REUTILIZAR | Gate de calidad |
| `RefreshDatabase` + factories | Patrón en tests existentes | ✅ Implementado | 🟢 REUTILIZAR | Factories deben incluir todos los campos NOT NULL |
| E2E Playwright | Sin `playwright.config.ts` ni directorio `e2e/` en el repo | 🔴 No existe | 🔴 DESARROLLAR | Requiere instalar Playwright y escribir flujos críticos |
| TypeScript `tsc --noEmit` | Pasa limpio (verificado) | ✅ Implementado | 🟢 REUTILIZAR | Ejecutar antes de cada build |
| Build `npm run build` | Genera 510 archivos sin errores (verificado) | ✅ Implementado | 🟢 REUTILIZAR | Gate mínimo antes de deploy |

---

## I. DEPLOYMENT

| Capacidad | Componente | Estado | Acción | Adaptar |
|-----------|-----------|--------|--------|---------|
| Script deploy backend | `docs/deploy-hostinger.md` — paramiko SSH | ✅ Implementado | 🟡 ADAPTAR | Cambiar `REMOTE` path y dominio |
| Script deploy frontend | `docs/deploy-hostinger.md` — paramiko SFTP | ✅ Implementado | 🟡 ADAPTAR | Cambiar `LOCAL_DIR` y `REMOTE_DIR` |
| Instrucciones iniciales de proyecto nuevo | `CLAUDE.md` global — `composer install`, `npm install`, `.env`, `migrate:fresh` | ✅ Implementado | 🟢 REUTILIZAR | Nada |
| FidelOS agentes (PEPE + equipo) | `CLAUDE.md` del proyecto + `agents/` | ✅ Implementado | 🟢 REUTILIZAR | Copiar `agents/` al nuevo proyecto |
| CI/CD automatizado | — | 🔴 No existe | 🔴 DESARROLLAR | Actualmente deploy manual por script |

---

## J. LANDING / WEB COMERCIAL

| Capacidad | Estado | Acción | Observaciones |
|-----------|--------|--------|---------------|
| Landing page pública | ✅ Implementado — `app/page.tsx` + `nosotros/` + `producto/` + `soluciones/` + `precios/` | 🟡 ADAPTAR | Reemplazar copy, imágenes, nombre de la vertical |
| SEO + sitemap + robots.txt | ⚠️ VERIFICAR — páginas existen; confirmar `sitemap.ts` / `robots.ts` en `app/` | ⚠️ VERIFICAR | — |
| Formulario de contacto / demo público | ✅ Implementado — `app/contacto/` con `ContactWizard` (wizard multi-paso) | 🟡 ADAPTAR | Copy y campos según la vertical |
| Blog / contenido de marketing | ✅ Implementado — `app/blog/` | 🟡 ADAPTAR | Artículos del sector |

---

## PROCEDIMIENTO PARA CREAR UNA NUEVA VERTICAL

### Paso 1 — Definir la vertical
- Nombre, sector y cliente objetivo.
- Módulos que necesita (ej. IPS: pacientes, citas, historia clínica, facturación médica).
- Módulos que NO necesita (ej. IPS probablemente no necesita reclutamiento).

### Paso 2 — Comparar contra esta matriz
Recorrer las secciones A–J y para cada capacidad asignar:
```
🟢 REUTILIZAR  — copiar el código tal cual
🟡 ADAPTAR     — tomar como base, modificar campos/lógica
🔴 DESARROLLAR — crear desde cero
⚪ NO NECESARIO
```

### Paso 3 — Identificar lo REUTILIZABLE
Todo lo marcado 🟢 en la matriz se hereda **sin tocar**:
- `BaseCrudController`, `TableQueryService`, `ResolvesCompany`
- `DataTable`, `ModuleTablePage`, `CrudModal`, `useApiTable`
- `Badge`, `badgeVariant`, `badgeLabel`
- `useSpeechInput`, `VoiceInputWrapper`
- `AdminShell` (estructura base)
- `AuditService`, `PendingService`, `CommunicationsButton`
- Deploy scripts (ajustando paths)
- Todos los servicios de dominio (StockService, PaymentService, etc.)

### Paso 4 — Identificar lo que requiere ADAPTACIÓN
Todo lo marcado 🟡. Para cada ítem listar:
- ¿Qué campos cambian?
- ¿Qué lógica cambia?
- ¿Qué labels/estados son diferentes?
Ejemplo:
- `badgeVariant` → agregar estados propios de la vertical
- `navGroups` → agregar módulos de la vertical
- Migración de permisos → crear `add_{vertical}_permissions.php`

### Paso 5 — Identificar lo que hay que DESARROLLAR
Solo lo marcado 🔴. Esta es la inversión real de la vertical:
- Tablas de BD propias de la vertical
- Models, Resources, FormRequests, Controllers
- Páginas Next.js en `app/(vertical)/`
- KPIs e indicadores del Dashboard del sector
- Lógica de negocio específica (ej. historia clínica, prescripciones)

### Paso 6 — Crear lista de módulos específicos
Listar cada módulo nuevo con:
- Nombre
- Entidades involucradas
- Relaciones con módulos CORE
- Permisos necesarios
- Campos principales

### Paso 7 — Crear plan de implementación
Seguir el orden de capas obligatorio (`docs/CORE_CONTRACT.md` §10):
```
BD → Backend Alignment Pass → Frontend/Formularios → Auth/RBAC → E2E
```
No avanzar una capa si la anterior no está cerrada.

Para cada módulo:
1. Migración de BD
2. Model + scopes
3. Resource JSON
4. FormRequest
5. Controller (extiende BaseCrudController)
6. Rutas en `routes/api.php`
7. Migración de permisos
8. Columnas DataTable + CrudFields en frontend
9. Página Next.js
10. Entrada en `navGroups`

### Paso 8 — Ejecutar desarrollo
- PEPE coordina el equipo FidelOS.
- SPEC-PLANNER genera el spec ejecutable por módulo.
- JUAN implementa.
- PEPE revisa antes de pasar a NANCY.
- NANCY supervisa técnicamente.
- Si NANCY rechaza → PEPE → JUAN → ciclo.

### Paso 9 — Ejecutar QA funcional
MARGARITA ejecuta:
- Flujos completos por módulo
- Pruebas de API (login, CRUD, permisos, relaciones)
- Pruebas de permisos (acceso denegado, IDOR)
- Pruebas responsive
- Pruebas dark/light mode

### Paso 10 — Ejecutar E2E
- Playwright: login, alta/edición de cada módulo core, flujos críticos del sector.
- Flujo offline de contingencia si aplica.
- ⚠️ Actualmente la suite E2E requiere verificación en este repo.

### Paso 11 — Verificar performance
- Ejecutar suite en SQLite y en MySQL.
- Verificar que toda tabla nueva tiene índices `(company_id, status)` y `(company_id, created_at)`.
- Revisar N+1 en queries de dashboard y listados.
- Verificar paginación — nunca cargar tablas completas.

### Paso 12 — Actualizar esta matriz
Al finalizar la vertical, actualizar `docs/SPEC.md`:
- Agregar módulos nuevos a la sección B o C.
- Registrar capacidades nuevas que otras verticales puedan reutilizar.
- Actualizar la sección de tests con los nuevos.
- Completar la **Matriz de Ahorro** (siguiente sección).

---

## MATRIZ DE AHORRO POR VERTICAL

> Registrar aquí cada vertical construida sobre FidelOS para medir el impacto del CORE.

| Vertical | Capacidades reutilizadas | Capacidades adaptadas | Capacidades desarrolladas | % reutilizado | Riesgos identificados | Dependencias especiales | Estado |
|----------|--------------------------|-----------------------|--------------------------|---------------|----------------------|------------------------|--------|
| **dfctalentohumano** (base) | — (es la fuente) | — | 40 módulos + CORE completo | — (es la base) | Tests pre-existentes fallan | PHP 8.4, Hostinger | ✅ En producción |
| IPS | Pendiente de medición | Pendiente de medición | Pendiente de medición | Pendiente | — | — | 🔴 No iniciado |
| Veterinaria | Pendiente de medición | Pendiente de medición | Pendiente de medición | Pendiente | — | — | 🔴 No iniciado |
| Car Wash | Pendiente de medición | Pendiente de medición | Pendiente de medición | Pendiente | — | — | 🔴 No iniciado |
| Inmobiliaria | Pendiente de medición | Pendiente de medición | Pendiente de medición | Pendiente | — | — | 🔴 No iniciado |

> Los porcentajes se calcularán al completar cada vertical con datos reales de módulos reutilizados vs. desarrollados.

---

## REGLA FUNDAMENTAL (no olvidar)

> **Una nueva vertical NO se empieza desde cero.**
>
> El Core es la base. Todo lo que ya existe, funciona y es reutilizable debe heredarse.
>
> Solo se invierte tiempo y dinero en:
> - Diferencias reales de la vertical (lógica de negocio específica)
> - Adaptaciones de campos y estados
> - Funcionalidades genuinamente nuevas
>
> **Duplicar trabajo = perder dinero.**
