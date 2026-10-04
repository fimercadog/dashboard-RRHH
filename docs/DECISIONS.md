# DECISIONS.md — Decisiones arquitectónicas tomadas

Las decisiones aquí son firmes. No revertirlas sin discusión explícita.

---

## D1 — ERP administrativo general, NO veterinario
**Fecha:** 2026-10-03
**Decisión:** Los módulos clínicos (Agenda, Citas, Pacientes, Servicios, Especies, Razas) fueron eliminados permanentemente.
**Por qué:** El proyecto fue copiado de una base veterinaria; esos módulos no aplican al ERP empresarial.
**ERP veterinario:** proyecto separado `demo-erp-web-veterinaria`. No mezclar.

## D2 — SQLite en desarrollo, MySQL en producción
**Fecha:** 2026-08-24
**Decisión:** Las migraciones evitan SQL específico de SQLite para compatibilidad con MySQL.
**Producción:** Hostinger con MySQL. Ver `docs/hostinger-deployment.md`.

## D3 — StockService como único escritor de stock
**Fecha:** 2026-10-01
**Decisión:** `stock_movements` y `product_stock` se escriben SOLO desde `StockService`.
**Por qué:** Garantiza consistencia del avg_cost y permite auditoría centralizada.
**Impacto:** Fases C (Compras) y D (Ventas) deben llamar a StockService, no escribir stock directamente.

## D4 — Diseño de integración C–F antes de código
**Fecha:** 2026-10-03
**Decisión:** Las tablas y flujos de Compras → Ventas → Finanzas → Contabilidad fueron diseñados en `docs/arquitectura-fases-c-f.md` ANTES de escribir código.
**Por qué:** Evita tener que modificar tablas al añadir cada fase.
**Ganchos ya en producción:** `stock_movements.journal_entry_id`, `stock_movements.posted_at`, `products.*_account_code`.

## D5 — Employee y User son entidades separadas
**Fecha:** 2026-08-24
**Decisión:** `users.employee_id` es nullable. Un usuario puede no ser empleado y un empleado puede no tener usuario.
**Por qué:** Flexibilidad para usuarios de sistema (Super Admin) y empleados sin acceso al panel.

## D6 — Permisos como migraciones, no como seeders ad hoc
**Fecha:** 2026-10-01
**Decisión:** Cada vertical nueva agrega sus permisos con una migración `add_{vertical}_permissions.php`.
**Por qué:** Los seeders generales re-ejecutan todo; las migraciones son idempotentes y tienen orden.

## D7 — BaseCrudController y ModuleTablePage no se modifican por verticales
**Fecha:** implícita, reforzada 2026-10-04
**Decisión:** Estos dos artefactos son el core. Si una vertical necesita algo especial, crea su propio controller/page. No modifica la base.

## D8 — Fase C y Fase D pueden desarrollarse en paralelo
**Fecha:** 2026-10-04 (documentado en `arquitectura-fases-c-f.md`)
**Decisión:** Compras (C) no depende de Ventas (D) ni viceversa. Ambas dependen de Inventario (B). Fase E depende de C y D. Fase F depende de todo.

## D9 — `APP_ENV=production` requiere `--force` en migraciones artisan
**Fecha:** aprendido en despliegue Hostinger
**Decisión:** En producción, usar `php artisan migrate --force`. Sin `--force`, artisan pregunta interactivamente y el proceso cuelga.

## D-FIN-1 — `payable_type` usa strings planos, no morphMap
**Fecha:** 2026-10-04
**Decisión:** `payments.payable_type` acepta `'accounts_receivable'` | `'accounts_payable'` como strings literales. PaymentService resuelve el modelo con un `match()` explícito.
**Por qué:** Laravel MorphTo requiere morphMap global o clases completas; el match explícito es más legible y no tiene efectos colaterales.

## D-FIN-2 — No se permiten saldos negativos en cuentas de caja
**Fecha:** 2026-10-04
**Decisión:** PaymentService rechaza con `LogicException` (→ 422 HTTP) cualquier operación que dejaría `cash_accounts.balance < 0`.
**Por qué:** Un saldo negativo en caja es imposible físicamente; indicaría un bug, no un caso de negocio válido.

## D-FIN-3 — `transfers` tabla independiente con cancelación compensatoria
**Fecha:** 2026-10-04
**Decisión:** Las transferencias entre cuentas crean un registro en `transfers` + dos `financial_transactions` (transfer_out / transfer_in). La cancelación NO borra estos registros; crea un par de transacciones compensatorias inversas.
**Por qué:** Trazabilidad completa. Cualquier movimiento puede ser reconstruido desde `financial_transactions` en orden cronológico.

## D-FIN-4 — Solo dos permisos para Finanzas MVP
**Fecha:** 2026-10-04
**Decisión:** `finance.manage` (escritura: cuentas, pagos, transferencias) y `finance.view` (solo movimientos). No granularidad por sub-módulo hasta que haya un caso de negocio real que lo requiera.

## D-FIN-5 — `payments.journal_entry_id` nullable como gancho de Fase F
**Fecha:** 2026-10-04
**Decisión:** La columna existe en BD pero siempre es NULL hasta que se implemente Contabilidad (Fase F).
**Por qué:** Evita una migración disruptiva cuando se active Contabilidad; el campo ya está en el fillable del modelo.

## D-FIN-6 — Finanzas es 100% manual, sin integración bancaria
**Fecha:** 2026-10-04
**Decisión:** El módulo Finanzas (CashAccount, Payment, Transfer, FinancialTransaction) es completamente interno. No existe ni existirá en el CORE actual ninguna conexión con APIs bancarias, Open Banking, sincronización automática de extractos ni conciliación bancaria automática.
**Alcance de CashAccount:** Es un registro interno del ERP (Caja principal, Caja menor, Cuenta Bancolombia, etc.). El nombre "cuenta bancaria" es solo identificativo dentro del sistema.
**Flujo:** Compras/Ventas → CxP/CxC → Pagos/Cobros manuales → CashAccount → FinancialTransaction → Contabilidad. Sin comunicación externa.
**Integración bancaria futura:** Si se requiere, debe tratarse como vertical/módulo independiente, NUNCA modificando el CORE. No diseñar para ello ahora.
**Por qué:** Mantener el CORE simple, seguro y auditable. La complejidad de Open Banking (credenciales, webhooks, extractos) no pertenece al ERP operativo básico.

## D-CON-1 — AccountingService como único escritor de journal_entries
**Fecha:** 2026-10-04
**Decisión:** `journal_entries` y `journal_entry_lines` se escriben SOLO desde `AccountingService`. Los servicios de dominio (PurchaseService, SaleService, PaymentService) llaman a `AccountingService::generateAndPost()` después de sus transacciones. Contabilidad NO puede escribir stock, cash_accounts.balance, CxC ni CxP.
**Por qué:** Mismo patrón que StockService. Garantiza partida doble, validaciones y trazabilidad centralizadas.

## D-CON-2 — Reversión como asiento compensatorio
**Fecha:** 2026-10-04
**Decisión:** Los asientos POSTED son inmutables. Las correcciones se hacen mediante `reverseEntry()` que crea un nuevo asiento con débitos/créditos invertidos. El original pasa a `reversed`. Nunca se borran ni editan asientos publicados.
**Por qué:** Principio de integridad contable. El historial completo es auditable en cualquier momento.

## D-CON-3 — Comportamiento silencioso cuando no hay período abierto
**Fecha:** 2026-10-04
**Decisión:** Si `generateAndPost()` no encuentra un período abierto para la fecha del documento, retorna `null` silenciosamente. La operación de negocio ya se completó; no se bloquea por falta de período.
**Por qué:** El ERP puede operar sin contabilidad activa. El usuario activa Contabilidad creando períodos; no debería romper operaciones previas.

## D-CON-4 — Plan de cuentas funcional (NO el PUC oficial)
**Fecha:** 2026-10-04
**Decisión:** El seeder crea una estructura funcional de referencia basada en el PUC colombiano. ⚠️ NO es el PUC oficial (Decreto 2649/1993). Debe validarse normativamente antes de usar en producción con obligaciones legales/fiscales.
**Por qué:** Proporcionar un punto de partida útil sin asumir responsabilidad normativa. El usuario puede ajustar las cuentas según sus necesidades.

## D-CON-5 — Asiento de apertura manual para MVP
**Fecha:** 2026-10-04
**Decisión:** El asiento de apertura de período se crea manualmente a través de `AccountingService::createOpeningEntry()`. Solo puede existir un asiento de apertura por período (idempotencia). Debe ser balanceado (suma débitos = suma créditos).
**Por qué:** La apertura automática requeriría conocer los saldos de cierre del período anterior, que en MVP podría no existir. El usuario introduce los saldos iniciales una vez.
