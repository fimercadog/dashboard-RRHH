# Arquitectura de integración — Fases C–F
## Compras · Ventas · Finanzas · Contabilidad

> Documento de diseño previo a implementación. Define tablas, flujos,
> reglas de integración y respuestas a las preguntas arquitectónicas
> que afectan las cuatro fases. **Leer antes de escribir cualquier migración.**

---

## 1. Cadena de documentos

```
CRM (Deal)
    │
    ▼
VENTAS ────────────────────────────────────────────────────┐
  Cotización → Pedido → Factura venta → CxC → Pago        │
                             │                              │
                    StockService::exit                      │
                             │                              ▼
                        INVENTARIO                      FINANZAS
                             │                       Caja / Bancos
                    StockService::entry                     │
                             │                              ▼
COMPRAS ────────────────────┘                        CONTABILIDAD
  Proveedor → OC → Recepción → Factura compra → CxP   Plan cuentas
                                                       Asientos
                                                       Diario/Mayor
```

---

## 2. Reglas de integración — las preguntas resueltas

### ¿Cuándo se mueve Inventario?

| Evento | Movimiento StockService | reference_type |
|--------|------------------------|----------------|
| Recepción de compra creada | `entry` | `purchase_receipt` |
| Factura de venta → status `posted` | `exit` | `sale_invoice` |
| Anulación de recepción | `exit` inverso | `purchase_receipt_cancellation` |
| Devolución de venta | `entry` inverso | `sale_invoice_return` |
| Ajuste manual | `adjustment` | — |

**Regla absoluta:** ningún módulo escribe `stock_movements` ni `product_stock` directamente. Solo `StockService`.

### ¿Cuándo nace una CxC / CxP?

- **CxC** → cuando `sale_invoices.status` cambia a `posted`
- **CxP** → cuando `purchase_invoices.status` cambia a `posted`
- Ambas crean un registro en `accounts_receivable` / `accounts_payable` con `balance = total`

### ¿Cuándo entra / sale dinero en Finanzas?

Al crear un `payment`, que:
1. Decrementa `cash_accounts.balance`
2. Crea un `financial_transaction`
3. Decrementa `accounts_receivable.balance` (cobro) o `accounts_payable.balance` (pago)
4. Si `balance = 0` → `status = paid`; si `balance < amount_original` → `partially_paid`

### ¿Qué documento origina cada asiento contable?

| Documento | Asiento generado | Debe | Haber |
|-----------|-----------------|------|-------|
| Factura venta (posted) | Ingreso por venta | CxC | Ingresos |
| Factura venta (posted) | Costo de venta | COGS | Inventario |
| Factura compra (posted) | Compra a crédito | Inventario | CxP |
| Pago de cliente | Cobro | Caja/Banco | CxC |
| Pago a proveedor | Pago | CxP | Caja/Banco |
| Ajuste de inventario | Ajuste | Inventario / Pérdida | — |

El asiento lo genera un `AccountingService` (Fase F) disparado al `posted` de cada documento. Escribe en `journal_entries` + `journal_entry_lines`, luego actualiza `stock_movements.journal_entry_id` y `posted_at`.

### ¿Cómo se calcula el costo?

- `product_stock.avg_cost` = costo promedio ponderado; lo mantiene `StockService` en cada `entry`
- Al generar un `exit` (factura de venta), `StockService` toma el `avg_cost` vigente y lo guarda en `stock_movements.unit_cost` y `sale_invoice_items.cost_at_time`
- `cost_at_time` es inmutable: el costo histórico de la venta no cambia aunque el avg_cost evolucione

### ¿Cómo se manejan anulaciones?

**Regla:** ningún documento financiero se borra. Se cancela creando un documento inverso.

| Situación | Acción |
|-----------|--------|
| Cancelar factura venta posted | `status = cancelled` + asiento de reversión + movimiento inverso StockService |
| Cancelar recepción de compra | `status = cancelled` + movimiento inverso StockService |
| Cancelar factura compra posted | `status = cancelled` + asiento de reversión + actualizar CxP |
| Cancelar pago | `status = cancelled` + transacción inversa + restaurar balance CxC/CxP |

### ¿Cómo se manejan pagos parciales?

- Cada `payment` registra el monto parcial
- `accounts_receivable/payable.balance` se decrementa en ese monto
- El status lo calcula el modelo: `balance == 0` → `paid`, `balance < amount` → `partially_paid`
- Múltiples payments pueden existir por una misma factura

### ¿Cómo se manejan devoluciones?

- **Devolución de venta:** nueva `sale_invoice` de tipo `return` (amount negativo) vinculada a la factura original, que genera `StockService::entry(reference_type='sale_invoice_return')` y asiento inverso
- **Devolución a proveedor:** nueva `purchase_receipt` de tipo `return` que genera `StockService::exit(reference_type='purchase_receipt_return')` y nota de crédito de proveedor

### ¿Cómo se corrige una factura?

No se edita. Se cancela y se emite una nueva. El historial queda íntegro.

---

## 3. Esquema de tablas

### Fase C — Compras

```sql
suppliers
  id, company_id, name, nit, email, phone, address,
  payment_terms (integer, días), status, timestamps

purchase_orders
  id, company_id, supplier_id, warehouse_id, user_id,
  number (unique por company), date, expected_date,
  status [draft|sent|partial|received|cancelled],
  subtotal, tax, total, notes, timestamps

purchase_order_items
  id, purchase_order_id, product_id,
  quantity, unit_cost, received_qty, timestamps

purchase_receipts
  id, company_id, purchase_order_id (nullable), supplier_id,
  warehouse_id, user_id, number, date, type [receipt|return],
  status [draft|posted|cancelled], notes, timestamps
  → al posted: StockService::entry / ::exit (si return)

purchase_receipt_items
  id, purchase_receipt_id, product_id,
  quantity, unit_cost, timestamps

purchase_invoices
  id, company_id, supplier_id, purchase_order_id (nullable),
  purchase_receipt_id (nullable), number, date, due_date,
  subtotal, tax, total,
  status [draft|posted|partially_paid|paid|cancelled],
  notes, timestamps
  → al posted: crea accounts_payable + journal_entry

accounts_payable
  id, company_id, supplier_id, purchase_invoice_id,
  amount, balance, due_date,
  status [open|partially_paid|paid|cancelled], timestamps
```

### Fase D — Ventas

```sql
quotes
  id, company_id, client_id, deal_id (nullable), user_id,
  number, date, valid_until,
  status [draft|sent|accepted|expired|cancelled],
  subtotal, discount_total, tax, total, notes, timestamps

quote_items
  id, quote_id, product_id,
  quantity, unit_price, discount_pct, subtotal, timestamps

sale_orders
  id, company_id, client_id, quote_id (nullable), user_id,
  warehouse_id, number, date,
  status [draft|confirmed|partial|fulfilled|cancelled],
  subtotal, discount_total, tax, total, notes, timestamps

sale_order_items
  id, sale_order_id, product_id,
  quantity, unit_price, discount_pct, subtotal,
  delivered_qty, timestamps

sale_invoices
  id, company_id, client_id, sale_order_id (nullable),
  user_id, number, date, due_date,
  type [invoice|return],
  status [draft|posted|partially_paid|paid|cancelled],
  subtotal, discount_total, tax, total, notes, timestamps
  → al posted: StockService::exit + crea accounts_receivable + journal_entry

sale_invoice_items
  id, sale_invoice_id, product_id,
  quantity, unit_price, discount_pct, subtotal,
  cost_at_time (avg_cost capturado al momento del exit),
  timestamps

accounts_receivable
  id, company_id, client_id, sale_invoice_id,
  amount, balance, due_date,
  status [open|partially_paid|paid|cancelled], timestamps
```

### Fase E — Finanzas

```sql
cash_accounts
  id, company_id, name,
  type [cash|bank|savings],
  balance, status, timestamps

payments
  id, company_id, cash_account_id, user_id,
  payable_type [sale_invoice|purchase_invoice],
  payable_id, amount, date,
  method [cash|transfer|check|card],
  reference (nro. transacción banco), notes,
  status [active|cancelled], timestamps
  → actualiza accounts_receivable/payable.balance
  → crea financial_transaction
  → (Fase F) crea journal_entry

financial_transactions
  id, company_id, cash_account_id,
  type [income|expense|transfer],
  amount, date,
  reference_type, reference_id,
  description, user_id, timestamps
```

### Fase F — Contabilidad

```sql
chart_of_accounts
  id, company_id, code (varchar 20), name,
  type [asset|liability|equity|income|expense],
  nature [debit|credit],
  parent_id (nullable, self-ref),
  allows_movements (bool), status, timestamps

accounting_periods
  id, company_id, name, start_date, end_date,
  status [open|closed], timestamps

journal_entries
  id, company_id, period_id, number, date,
  reference_type, reference_id,
  description, status [draft|posted], user_id, timestamps

journal_entry_lines
  id, journal_entry_id, account_id,
  debit (decimal 14,4), credit (decimal 14,4),
  description, timestamps
  → constraint: debit XOR credit (no ambos en cero, no ambos > 0)
```

**Al postear un journal_entry:**
- Si `reference_type = 'stock_movement'` → `stock_movements.journal_entry_id = entry.id`, `posted_at = now()`
- `accounting_periods.balance` se recalcula (o se hace on-the-fly en reports)

---

## 4. Servicios nuevos (mínimos)

| Servicio | Responsabilidad |
|----------|----------------|
| `PurchaseService` | Confirmar OC, crear recepción, delegar a StockService |
| `SaleService` | Confirmar factura venta, delegar a StockService, crear CxC |
| `PaymentService` | Registrar pago, actualizar balance CxC/CxP, crear transacción financiera |
| `AccountingService` | Generar journal_entry al posted de cualquier documento (Fase F) |

`StockService` existente no se modifica — solo se llama desde los servicios nuevos con los `reference_type` correctos.

---

## 5. Permisos (Spatie) por fase

```
Fase C: purchases.manage | purchases.view
Fase D: sales.manage | sales.view
Fase E: finance.manage | finance.view
Fase F: accounting.manage | accounting.view | accounting.close_period
```

---

## 6. Orden de implementación recomendado

1. **Fase C completa** → tablas + servicios + UI + tests
2. **Fase D completa** → tablas + servicios + UI + tests (depende de Inventario, no de C)
3. **Fase E** → Finanzas (depende de C y D para pagos)
4. **Fase F** → Contabilidad (depende de todo lo anterior)

C y D pueden desarrollarse en paralelo si se necesita velocidad.

---

## 7. Lo que NO cambia

- Stack: SQLite + Laravel + Next.js
- `StockService` — inmutable, solo se llama
- `BaseCrudController` — no se toca
- `ModuleTablePage` — no se toca
- Tablas existentes de Inventario, CRM, RRHH — no se modifican
- `reference_type / reference_id` en `stock_movements` — ya preparados
- `journal_entry_id / posted_at` en `stock_movements` — ya preparados
- `inventory_account_code / cogs_account_code / sale_account_code` en `products` — ya preparados
