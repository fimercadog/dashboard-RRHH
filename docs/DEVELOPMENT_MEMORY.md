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

## M14 — Tablas de líneas de documentos no llevan company_id
**Regla:** `purchase_order_items`, `purchase_receipt_items` (y futuras `sale_order_items`, etc.) NO tienen `company_id`.
La multitenancy se resuelve por JOIN con la tabla padre.
Solo las tablas de cabecera (suppliers, purchase_orders, purchase_receipts, purchase_invoices, accounts_payable) llevan `company_id`.
