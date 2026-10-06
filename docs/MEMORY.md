# MEMORY — Reglas técnicas vigentes

Solo guardar reglas reutilizables, validadas y actualmente vigentes. No usar como diario.

## BD y multitenancy
- Toda tabla operativa nueva debe tener `company_id` cuando corresponda al modelo de negocio.
- Las FKs hacia datos company-scoped deben validarse con `ownedExists()` y respetar el `company_id` del usuario autenticado.
- Las tablas de líneas de documentos siguen el contrato definido por su tabla padre; no duplicar `company_id` sin una razón arquitectónica validada.

## Servicios de dominio
- `StockService` es el único escritor de `stock_movements` y `product_stock`.
- `PaymentService` es el único escritor de saldos de `cash_accounts` y de `financial_transactions`.
- `PurchaseService` centraliza el posteo de compras y sus efectos de inventario/CxP.
- `SaleService` centraliza el posteo de ventas y sus efectos de inventario/CxC.
- `AccountingService` es el único escritor de `journal_entries` y `journal_entry_lines`.

## CORE
- `BaseCrudController` y los componentes CORE compartidos no se modifican para resolver una necesidad específica de una vertical; resolver el comportamiento específico en la capa de la vertical.
- Buscar antes de crear: reutilizar → extender → crear.
- No implementar una capa sobre una BD provisional. Cerrar primero el contrato de datos real.

## Formularios y API
- FK company-scoped: usar `ApiFormRequest` + `ownedExists()`.
- Los selectores de entidades deben devolver solo la información necesaria y permanecer scoped por empresa.
- Archivos: recibir archivo real, guardar ruta relativa y exponer URL mediante Resource.
- No exponer IDs técnicos como sustituto de un selector legible en UI.
- Estados deben tener valores por defecto explícitos cuando corresponda.

## Tests
- En tests Laravel protegidos por Sanctum, usar el guard correcto (`actingAs(..., 'sanctum')`).
- Con `RefreshDatabase`, crear explícitamente roles/permisos que el test necesite; no asumir que los seeders corrieron.
- Los factories deben proporcionar todos los campos NOT NULL sin default.
- Ejecutar contra SQLite y verificar compatibilidad con MySQL cuando el cambio pueda verse afectado por diferencias entre motores.

## Operación
- Cuando `APP_ENV=production`, los comandos Artisan que requieren confirmación deben usar `--force` cuando corresponda.
- Para SSH directo usar OpenSSH; no usar PuTTY/Plink/PuTTYgen/PSCP en automatizaciones.
