# DEVELOPMENT_MEMORY.md — Conocimiento persistente y reutilizable

> Solo conocimiento útil y no obvio. No un diario. Actualizar cuando hay nuevo aprendizaje.

---

## M1 — APP_ENV=production congela artisan sin --force
**Aprendido:** Hostinger deploy
**Regla:** `php artisan migrate --force` (y `db:seed --force`) en cualquier entorno donde `APP_ENV=production`.
Sin `--force`, el proceso espera confirmación interactiva que nunca llega → cuelga.

## M2 — StockService es el único escritor de stock
**Regla:** Ningún código escribe `stock_movements` o `product_stock` directamente.
Solo `StockService::entry()`, `::exit()`, `::adjustment()`, `::transfer()`.
**Por qué:** Mantiene consistencia de avg_cost y trazabilidad.
**Aplicar siempre:** Fases C, D y cualquier módulo futuro que mueva stock.

## M3 — Diseño de integración multi-fase antes de código
**Regla:** Cuando hay ≥2 fases que se integran (Compras→Ventas→Finanzas→Contabilidad),
diseñar el esquema completo de tablas y flujos ANTES de escribir la primera migración.
**Por qué:** Descubrir después que la tabla de Compras necesita un campo para Contabilidad obliga a modificar tablas ya en producción.
**Referencia:** `docs/arquitectura-fases-c-f.md`

## M4 — BaseCrudController auto-detecta FormRequest
**Regla:** Si existe `App\Http\Requests\Store{Model}Request`, BaseCrudController lo usa automáticamente en store y update (update con `sometimes`).
**No hace falta** override del controller solo para validar.

## M5 — Módulos veterinarios eliminados permanentemente
**Regla:** Migrations `remove_vet_clinical_modules` (2026-10-03) eliminaron Species, Breeds, Patients, Services, Appointments.
No recrear estos módulos en este proyecto. ERP veterinario = proyecto separado.

## M6 — company_id en toda tabla operativa
**Regla:** Toda tabla de datos de negocio tiene `company_id` FK a `companies`.
`ResolvesCompany` concern lo resuelve del usuario autenticado.
Jamás omitir company_id en una migración nueva.

## M7 — Permisos nuevos van en migración, no en seeder
**Regla:** `add_{vertical}_permissions.php` con la lista exhaustiva de permisos.
Los seeders de roles/permisos son para los permisos base; las migraciones de verticales añaden los suyos.

## M8 — Una tarea = todas las capas, no solo backend
**Regla:** Toda tarea funcional tiene: migración + modelo + controller + recurso JSON + ruta + frontend + nav.
No declarar DONE si alguna capa falta.
**Síntoma del problema:** "Hiciste el backend pero faltó el frontend." / "Faltó registrar la ruta."

## M9 — La integración de ganchos de Contabilidad ya existe en Inventario
**Regla:** `stock_movements.journal_entry_id`, `stock_movements.posted_at`, `products.*_account_code` ya están en la BD como NULL.
En Fase F, `AccountingService` los rellena. No hay que agregar esas columnas entonces.

## M10 — BaseCrudController, ModuleTablePage y StockService son inmutables para verticales
**Regla:** Si una vertical necesita algo que estos artefactos no dan, escribe el comportamiento específico en el controller/page de esa vertical, sin modificar la base.
Modificar la base puede romper todas las verticales simultáneamente.

## M11 — `migrate:fresh --seed` como check de sanidad
**Regla:** Antes de declarar cualquier vertical como DONE, correr `php artisan migrate:fresh --seed` sin errores.
Si falla, la vertical no está terminada aunque "compile".

## M12 — Tests HTTP API usan `actingAs($user, 'sanctum')`
**Aprendido:** Fase C (PurchasesTest)
**Regla:** Las rutas protegidas por `auth:sanctum` en tests Laravel requieren `$this->actingAs($user, 'sanctum')`, no `$this->actingAs($user)` (sin guard).
Sin el guard, la autenticación no activa el sanctum guard y las rutas devuelven 401.
**Referencia:** `tests/Feature/PurchasesTest.php`

## M13 — PurchaseService es el único escritor de facturas y CxP de compra
**Regla:** Para postear una recepción, llamar `PurchaseService::postReceipt()` (llama StockService internamente).
Para postear una factura, llamar `PurchaseService::postInvoice()` (crea AccountPayable).
No escribir stock_movements, product_stock ni accounts_payable directamente desde Controllers.

## M15 — SaleService es el único escritor de facturas y CxC de venta
**Regla:** Para postear una factura de venta, llamar `SaleService::postInvoice()` (llama StockService::exit() + crea AccountsReceivable).
Para crear una devolución, llamar `SaleService::createReturn()` (revierte stock + CxC negativa).
No escribir stock_movements, product_stock ni accounts_receivable directamente desde Controllers.
**Referencia:** M13 (análogo en Compras).

## M16 — Client.first_name y Client.last_name son NOT NULL
**Aprendido:** SalesTest setUp
**Regla:** Al crear un Client en tests, incluir siempre `first_name`, `last_name` y `name`.
`Client::create(['company_id'=>..., 'first_name'=>'X', 'last_name'=>'Y', 'name'=>'X Y', 'status'=>'active'])`.

## M17 — ProductStock.company_id es NOT NULL
**Aprendido:** SalesTest setUp
**Regla:** Al crear un ProductStock directo en tests, incluir `company_id`.

## M19 — Leads: formulario público → módulo Leads del ERP
**Aprendido:** Wizard de contacto (2026-10-05)
**Regla:** El endpoint público `POST /api/leads` (sin auth, throttle 5/min) es suficiente para cualquier vertical.
Campos: `name`, `company_name`, `email`, `phone`, `employee_count`, `priority_module`, `message`, `source`, `consent`.
No hay `company_id` en leads — es tabla plataforma, no tenant.
`priority_module` y `employee_count` son strings libres: las opciones se definen en frontend (pills).
`ContactForm` para formulario plano; `ContactWizard` para flujo multi-paso de calificación.
Para verticales futuras: copiar `ContactWizard`, cambiar `source` y la lista de módulos.
**Bug conocido:** `ErpLeadSource` (Communications) referencia `company_id` que no existe en la migración → falla en tiempo de ejecución. No afecta creación de leads pero bloquea `CampaignService` si usa esta fuente.

## M18 — PaymentService es el único escritor de saldos de caja y transacciones financieras
**Regla:** Ningún código escribe `cash_accounts.balance` ni `financial_transactions` directamente.
Solo `PaymentService::registerPayment()`, `::cancelPayment()`, `::transfer()`, `::cancelTransfer()`.
**Por qué:** Análogo a StockService para stock. Garantiza auditoría centralizada y consistencia de saldos.
**Aplicar siempre:** Fase F (Contabilidad) también debe pasar por PaymentService, no escribir cash_accounts directamente.

## M19 — En tests, accounts_receivable y accounts_payable requieren padres reales (NOT NULL FK)
**Aprendido:** FinanceTest setUp
**Regla:** `accounts_receivable.sale_invoice_id` y `accounts_payable.purchase_invoice_id` son NOT NULL.
Al crear estos registros en tests, primero crear un `SaleInvoice` / `PurchaseInvoice` real y pasar su ID.
No intentar crear CxC/CxP con `sale_invoice_id = null`.

## M20 — Badge del proyecto no acepta prop `variant`
**Aprendido:** Build de Finanzas
**Regla:** `src/components/ui/badge.tsx` solo acepta `className: string`. No tiene prop `variant`.
Para distintos estilos usar Tailwind directo: `className="bg-muted text-muted-foreground"`, `"bg-destructive/10 text-destructive"`, `"border border-input bg-transparent"`.

## M21 — FormRequests usan `ownedExists()` para validar FKs con company_id
**Aprendido:** K1 — Autorización
**Regla:** Para campos FK a tablas company-scoped, NO usar `'exists:TABLE,id'` sino `$this->ownedExists('TABLE')`.
`ApiFormRequest::ownedExists()` aplica `Rule::exists(TABLE)->where('company_id', $cid)` cuando hay usuario autenticado, y cae a `'exists:TABLE,id'` en el path de update de BaseCrudController (que usa `new $class` sin contexto de auth).
**Por qué:** `exists:TABLE,id` sin company_id permite pasar IDs de otra empresa (cross-company FK contamination).
**Tablas afectadas:** suppliers, clients, warehouses, products, purchase_orders, purchase_receipts, sale_orders, quotes, deals, employees, departments, positions, categories, brands, units.

## M22 — `with('model:id,name')` falla en MySQL si la columna no existe (SQLite silencioso)
**Aprendido:** K4 — Smoke tests MySQL
**Regla:** En `$with` de controladores, NO usar `model:id,name` si el modelo no tiene columna `name`. Los accessors de Eloquent (como `getFullNameAttribute`) no son columnas reales. SQLite ignora el error (devuelve NULL), MySQL lanza `Unknown column`. Usar las columnas reales: `client:id,first_name,last_name`. En el Resource, usar `$this->client->full_name` (el accessor).
**Columnas afectadas:** `clients` no tiene `name` — tiene `first_name`, `last_name`, accessor `full_name`.

## M23 — Patrón de idempotencia para módulos de contingencia
**Aprendido:** K7 — Extensión contingencia offline
**Regla:** Para agregar un módulo a contingencia:
1. Migración: `client_uuid` UUID nullable unique en la tabla.
2. Modelo: `'client_uuid'` en `$fillable`.
3. FormRequest: `'client_uuid' => ['nullable', 'uuid']` en las reglas.
4. Controller: guard al inicio de `store()` — si `$request->filled('client_uuid')` y existe registro con ese UUID, devuelve 200 sin crear. Modelos simples usan `firstOrCreate`; con items complejos, lookup previo + retorno.
5. Registry: entrada en `ContingencyModuleRegistry::all()`.
6. Frontend adapter: `summarize()` + `sync()` con `client_uuid` en `adapters.ts`.
**Elegibilidad:** Solo operaciones puramente aditivas (crean filas, nunca modifican ni dependen de estado actual). Stock movements, payments, invoice posting = NO elegibles.

## M14 — Tablas de líneas de documentos no llevan company_id
**Regla:** `purchase_order_items`, `purchase_receipt_items` (y futuras `sale_order_items`, etc.) NO tienen `company_id`.
La multitenancy se resuelve por JOIN con la tabla padre.
Solo las tablas de cabecera (suppliers, purchase_orders, purchase_receipts, purchase_invoices, accounts_payable) llevan `company_id`.

## M24 — PendingProvider: cada vertical registra su propio provider, no duplica pendientes de otra
**Aprendido:** CORE — Pendientes y Comunicaciones
**Regla:** Cada vertical crea e implementa su `PendingProvider` y lo registra en `AppServiceProvider::boot()`. Ownership estricto: CxP → Compras, CxC → Ventas, Overdraft → Finanzas. Un provider nunca consulta tablas de otra vertical.
**Por qué:** Evita doble-conteo en el badge y viola la separación de verticales.

## M25 — Tests con roles Spatie: crear con Role::firstOrCreate en setUp, nunca assignRole('nombre') sin crear el rol
**Aprendido:** CORE — tests PendingTest + CommunicationTest
**Regla:** Con `RefreshDatabase`, los seeders no corren. Si un test usa `assignRole('Administrador de empresa')` sin crear el rol primero, lanza `RoleDoesNotExist`. Siempre crear roles con `Role::firstOrCreate` y asignar permisos con `Permission::firstOrCreate` en el `setUp()`.
**Por qué:** `CompanyFactory` tiene definición vacía (igual que `EmployeeFactory` original). Todos los factories con definición vacía requieren que se les pasen los campos obligatorios explícitamente.

## M26 — Factories vacíos: employee_code, start_date, identification_number son NOT NULL en SQLite
**Aprendido:** CORE — tests PendingTest
**Regla:** `EmployeeFactory`, `VacationRequestFactory` y `PermissionRequestFactory` tenían definición vacía `[]`. Con `RefreshDatabase`, insertar sin esos campos lanza `NOT NULL constraint failed`. Siempre llenar en `definition()` los campos NOT NULL sin default. Columnas con `default()` en la migración pueden omitirse en el factory.
**Por qué:** SQLite es estricto con NOT NULL. Descubrirlo en tests es el momento correcto (no en prod).
