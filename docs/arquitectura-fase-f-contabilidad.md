# Diseño Arquitectónico — Fase F: Contabilidad
> Estado: **BORRADOR — pendiente aprobación**.
> Fecha: 2026-10-04. No implementar hasta recibir aprobación explícita.
> Leer junto a: `docs/arquitectura-fases-c-f.md`, `docs/CORE_CONTRACT.md`

---

## 0. Principio fundamental

**Contabilidad no es la fuente de verdad de ningún otro módulo.**

Los módulos existentes (Inventario, Compras, Ventas, Finanzas) siguen siendo dueños de sus datos.
`AccountingService` consume los hechos económicos ya registrados por esos módulos y los traduce a
lenguaje contable. La dirección es siempre hacia abajo:

```
Inventario  ────────────────────────────────────────┐
Compras ─────────────────────────────────────────── │
Ventas  ──────────────────────────────────────────── ├──► CONTABILIDAD
Finanzas ─────────────────────────────────────────── │    (solo lee + registra)
                                                      │
             AccountingService es el ÚNICO escritor ──┘
             de: journal_entries + journal_entry_lines
```

`AccountingService` **nunca** modifica:
- `stock_movements.quantity` / `product_stock.quantity`
- `cash_accounts.balance`
- `accounts_receivable.balance`
- `accounts_payable.balance`

Solo **rellena los ganchos** que ya están NULL en esas tablas:
- `stock_movements.journal_entry_id` / `stock_movements.posted_at`
- `payments.journal_entry_id`
- (nuevos ganchos propuestos, ver sección 14)

---

## 1. Entidades del módulo Contabilidad

| Entidad | Tabla | Descripción |
|---------|-------|-------------|
| Plan de cuentas | `chart_of_accounts` | Árbol jerárquico de cuentas contables |
| Período contable | `accounting_periods` | Ventana de tiempo: mes, trimestre o año |
| Asiento contable | `journal_entries` | Encabezado del asiento (fuente, fecha, descripción) |
| Línea contable | `journal_entry_lines` | Cada debe/haber del asiento |

---

## 2. Tablas — esquema propuesto

### 2.1 `chart_of_accounts`

```
id                    bigint unsigned PK
company_id            FK companies (cascade delete)
code                  varchar(20) — código contable (ej. "1105", "41")
name                  varchar(150) — nombre de la cuenta
type                  enum: asset | liability | equity | revenue | expense | cost
nature                enum: debit | credit  — naturaleza normal del saldo
parent_id             FK self (nullable) — cuenta padre en la jerarquía
level                 tinyint unsigned — profundidad en el árbol (1=clase, 2=grupo, 3=cuenta, 4=subcuenta)
allows_movements      boolean default false — true: puede recibir líneas de asiento
status                enum: active | inactive
notes                 text nullable
timestamps
UNIQUE (company_id, code)
INDEX (company_id, parent_id)
```

**Regla:** solo cuentas con `allows_movements=true` y `status=active` pueden aparecer en `journal_entry_lines`.
Las cuentas padre (nodo intermedio) tienen `allows_movements=false` y solo sirven para agrupar.

### 2.2 `accounting_periods`

```
id                    bigint unsigned PK
company_id            FK companies (cascade delete)
name                  varchar(100) — ej. "Enero 2026", "Q1 2026"
start_date            date NOT NULL
end_date              date NOT NULL
status                enum: open | closed
closed_by             FK users nullable — quién cerró el período
closed_at             timestamp nullable
timestamps
INDEX (company_id, status)
INDEX (company_id, start_date, end_date)
CONSTRAINT: no overlap con otro período de la misma empresa
```

**Reglas:**
- Solo puede haber UN período `open` por empresa en un rango de fechas dado (se verifica en AccountingService antes de crear).
- Un período `closed` nunca se reabre (regla de negocio, no constraint de BD).

### 2.3 `journal_entries`

```
id                    bigint unsigned PK
company_id            FK companies (cascade delete)
accounting_period_id  FK accounting_periods (restrict on delete)
number                varchar(30) — generado: "JE-2026-001", autoincrement por empresa + año
date                  date NOT NULL — debe caer dentro del rango del período
description           varchar(200) NOT NULL
status                enum: draft | posted | reversed
reference_type        varchar(50) nullable — 'purchase_invoice' | 'sale_invoice' | 'payment' | 'transfer' | 'opening' | 'closing' | 'manual'
reference_id          bigint unsigned nullable
reversed_by_entry_id  FK self nullable — si este asiento fue revertido, apunta al asiento de reversión
reversal_of_entry_id  FK self nullable — si este asiento ES una reversión, apunta al original
user_id               FK users (restrict)
timestamps
INDEX (company_id, accounting_period_id)
INDEX (company_id, reference_type, reference_id)  ← trazabilidad
INDEX (company_id, date)
INDEX (status)
UNIQUE (company_id, number)
```

**Estados:**
- `draft`: creado pero no contabilizado. Se puede editar y borrar.
- `posted`: contabilizado. **Inmutable.** Solo se puede revertir (genera nuevo asiento).
- `reversed`: fue revertido. El campo `reversed_by_entry_id` apunta al asiento compensatorio. El asiento original queda visible para auditoría; su efecto contable es neutralizado por la reversión.

### 2.4 `journal_entry_lines`

```
id                    bigint unsigned PK
journal_entry_id      FK journal_entries (cascade delete) — solo se borra en draft
account_id            FK chart_of_accounts (restrict)
description           varchar(200) nullable — detalle de la línea
debit                 decimal(14,4) default 0 — 0 si la línea es crédito
credit                decimal(14,4) default 0 — 0 si la línea es débito
sequence              smallint unsigned — orden de la línea dentro del asiento
timestamps
INDEX (journal_entry_id)
INDEX (account_id)
```

**Invariantes (aplicados en AccountingService, no en BD):**
1. `debit >= 0` y `credit >= 0`.
2. NOT (debit > 0 AND credit > 0): una línea no puede ser débito y crédito simultáneamente.
3. NOT (debit == 0 AND credit == 0): una línea vacía no tiene sentido.
4. Al postear: `SUM(lines.debit) = SUM(lines.credit)` — partida doble.
5. Todas las cuentas en las líneas deben pertenecer a la misma `company_id` que el asiento.
6. Todas las cuentas deben tener `allows_movements=true` y `status=active`.

---

## 3. Tipos de cuenta y naturaleza del saldo

| Tipo | Nombre | Naturaleza normal | Aumenta con | Disminuye con |
|------|--------|-------------------|-------------|---------------|
| `asset` | Activo | Débito | Débito | Crédito |
| `liability` | Pasivo | Crédito | Crédito | Débito |
| `equity` | Patrimonio | Crédito | Crédito | Débito |
| `revenue` | Ingresos | Crédito | Crédito | Débito |
| `expense` | Gastos | Débito | Débito | Crédito |
| `cost` | Costos | Débito | Débito | Crédito |

**Cómo calcular el saldo de una cuenta:**
```
saldo = SUM(lines.debit) - SUM(lines.credit)   — si nature='debit'
saldo = SUM(lines.credit) - SUM(lines.debit)   — si nature='credit'
```

Un saldo positivo es normal; uno negativo es anómalo (salvo ajustes específicos).

---

## 4. Plan de cuentas — estructura jerárquica propuesta

Esta es una estructura de referencia para el ERP empresarial colombiano. **No es el PUC oficial** — es una simplificación funcional que permite mapear los hechos económicos de los módulos existentes.

⚠️ **VALIDAR NORMATIVAMENTE** antes de declarar compatibilidad con el PUC (Decreto 2649/1993, NIIF).

```
1  ACTIVO                              (asset, debit, parent=null, allows_movements=false)
   11  Disponible                     (asset, debit, parent=1, allows_movements=false)
       1105  Caja                     (asset, debit, parent=11, allows_movements=true)
       1110  Bancos                   (asset, debit, parent=11, allows_movements=true)
       1115  Cuentas de ahorro        (asset, debit, parent=11, allows_movements=true)
   13  Deudores                       (asset, debit, parent=1, allows_movements=false)
       1305  Clientes (CxC)           (asset, debit, parent=13, allows_movements=true)
   14  Inventarios                    (asset, debit, parent=1, allows_movements=false)
       1435  Mercancías               (asset, debit, parent=14, allows_movements=true)
   15  Propiedades planta y equipo    (asset, debit, parent=1, allows_movements=false)
       1504  Terrenos                 (asset, debit, parent=15, allows_movements=true)
       ...

2  PASIVO                             (liability, credit, parent=null)
   22  Proveedores                    (liability, credit, parent=2)
       2205  Proveedores nacionales (CxP) (liability, credit, parent=22, allows_movements=true)
   24  Impuestos, gravámenes y tasas  (liability, credit, parent=2)
       2408  IVA por pagar            (liability, credit, parent=24, allows_movements=true)

3  PATRIMONIO                         (equity, credit, parent=null)
   31  Capital social                 (equity, credit, parent=3)
       3105  Capital suscrito         (equity, credit, parent=31, allows_movements=true)
   36  Resultados del ejercicio       (equity, credit, parent=3)
       3605  Utilidad del ejercicio   (equity, credit, parent=36, allows_movements=true)
       3610  Pérdida del ejercicio    (equity, debit,  parent=36, allows_movements=true)
   37  Resultados de ejercicios anteriores
       3705  Utilidades acumuladas    (equity, credit, parent=37, allows_movements=true)

4  INGRESOS                           (revenue, credit, parent=null)
   41  Ingresos operacionales         (revenue, credit, parent=4)
       4135  Comercio al por mayor    (revenue, credit, parent=41, allows_movements=true)
       4155  Servicios                (revenue, credit, parent=41, allows_movements=true)

5  GASTOS                             (expense, debit, parent=null)
   51  Gastos operacionales de admon  (expense, debit, parent=5)
       5105  Gastos de personal       (expense, debit, parent=51, allows_movements=true)
       5195  Diversos                 (expense, debit, parent=51, allows_movements=true)

6  COSTOS                             (cost, debit, parent=null)
   61  Costo de ventas                (cost, debit, parent=6)
       6135  Comercio al por mayor    (cost, debit, parent=61, allows_movements=true)
```

**Mapeo con los módulos del ERP:**

| Módulo / evento | Cuenta Debe | Cuenta Haber |
|-----------------|-------------|--------------|
| Pago a proveedor (`payable`) | CxP (2205) | Caja/Banco (1105/1110) |
| Cobro de cliente (`receivable`) | Caja/Banco (1105/1110) | CxC (1305) |
| Factura de venta → reconocimiento | CxC (1305) | Ingresos (4135/4155) |
| Factura de venta → costo | Costo de ventas (6135) | Inventarios (1435) |
| Factura de compra | Inventarios (1435) | CxP (2205) |
| Transferencia entre cuentas | Banco destino | Banco origen |
| Ajuste de inventario + | Inventarios (1435) | Ajustes inventario (cuenta 5xxx) |
| Ajuste de inventario - | Ajustes inventario | Inventarios (1435) |

⚠️ Los códigos de cuenta son configurables por empresa. El mapeo entre `reference_type` y las cuentas
contables se hace a través de un **registro de cuentas de integración** (ver sección 13).

---

## 5. Períodos contables — flujo de estados

```
(no existe)
     │ openPeriod()
     ▼
   open  ──── createJournalEntry() ───► pueden crearse asientos con fecha en este período
     │
     │ closePeriod()  (solo accounting.close)
     ▼
  closed  ──── ninguna operación puede crear/modificar asientos con fecha en este período
```

**Reglas:**
- Un período se crea con fechas inicio/fin. Puede existir solo uno `open` que contenga la fecha actual.
- Al intentar crear un asiento cuya `date` caiga en un período `closed`, AccountingService rechaza con excepción.
- Un asiento de un período `closed` **nunca se modifica**. La corrección se hace con una reversión
  en el período `open` actual.
- Solo usuarios con permiso `accounting.close` pueden cerrar períodos.
- El cierre genera automáticamente los asientos de cierre (sección 9).

**Sobre períodos solapados:** Al crear un período se verifica que `start_date`/`end_date` no se
solapen con otro período existente de la misma empresa. Si hay solapamiento, AccountingService rechaza.

---

## 6. Flujo contable completo

```
HECHO ECONÓMICO (en módulo origen)
      │
      │  el módulo llama AccountingService::generateEntry(type, referenceId, companyId, userId)
      ▼
AccountingService resuelve:
  1. Obtiene el período abierto que contiene la fecha del documento
  2. Determina las cuentas según el tipo de referencia y las cuentas de integración
  3. Crea JournalEntry (status=draft) + JournalEntryLines
  4. Valida partida doble (sum_debit == sum_credit)
  5. Si OK → postEntry() → status=posted
  6. Llena los ganchos en el documento origen (journal_entry_id, posted_at)
      │
      ▼
   POSTED — inmutable. Auditable. Trazable.
```

**Fase MVP:** draft + postEntry() ocurren en la misma transacción (auto-post).
No se expone la API de draft al usuario para simplificar.

**Fase futura:** se puede exponer draft para revisión manual antes de contabilizar.

---

## 7. AccountingService — interfaz propuesta

```php
class AccountingService
{
    // Genera y postea un asiento desde un documento origen.
    // Lanzar LogicException si: período cerrado, cuentas inactivas, partida descuadrada.
    public function generateAndPost(
        string $referenceType,
        int    $referenceId,
        int    $companyId,
        int    $userId
    ): JournalEntry;

    // Genera asiento sin postear (para revisión manual — fase futura).
    public function generateDraft(
        string $referenceType,
        int    $referenceId,
        int    $companyId,
        int    $userId
    ): JournalEntry;

    // Contabiliza un asiento en estado draft.
    public function postEntry(JournalEntry $entry, int $userId): void;

    // Crea asiento de reversión en el período abierto actual.
    // El asiento original sigue como status='reversed'; no se borra.
    public function reverseEntry(JournalEntry $entry, int $userId): JournalEntry;

    // Apertura de período.
    public function openPeriod(
        int    $companyId,
        string $name,
        string $startDate,
        string $endDate,
        int    $userId
    ): AccountingPeriod;

    // Cierre de período: genera asientos de cierre + marca como closed.
    public function closePeriod(AccountingPeriod $period, int $userId): void;

    // Asiento de apertura: carga de saldos iniciales de cuentas de balance.
    public function createOpeningEntry(
        AccountingPeriod $period,
        array            $accountBalances,  // [account_id => ['debit' => x, 'credit' => y]]
        int              $userId
    ): JournalEntry;
}
```

`AccountingService` es el **único escritor** de `journal_entries` y `journal_entry_lines`.
Ningún controller escribe en estas tablas directamente.

---

## 8. Integración con los módulos existentes

### 8.1 Dónde se llama AccountingService

La llamada ocurre DENTRO de los servicios existentes, en el mismo `DB::transaction`:

| Servicio | Evento | Llamada AccountingService |
|---------|--------|--------------------------|
| `PurchaseService::postInvoice()` | invoice.status → posted | `generateAndPost('purchase_invoice', $invoice->id, ...)` |
| `SaleService::postInvoice()` | invoice.status → posted | `generateAndPost('sale_invoice', $invoice->id, ...)` |
| `PaymentService::registerPayment()` | payment creado | `generateAndPost('payment', $payment->id, ...)` |
| `PaymentService::cancelPayment()` | payment cancelado | `reverseEntry($payment->journalEntry, ...)` |
| `PaymentService::transfer()` | transfer creado | `generateAndPost('transfer', $transfer->id, ...)` |
| `PaymentService::cancelTransfer()` | transfer cancelado | `reverseEntry($transfer->journalEntry, ...)` |

**Esta llamada solo se añade cuando Fase F esté aprobada.** Hoy los servicios no llaman a AccountingService.

### 8.2 Asientos por tipo de referencia

**`sale_invoice` (posted):**
```
Asiento 1 — Reconocimiento de ingreso:
  Debe:  CxC (1305) por invoice.total
  Haber: Ingresos (4135/4155) por invoice.total

Asiento 2 — Costo de ventas:
  Debe:  Costo de ventas (6135) por SUM(item.cost_at_time × item.quantity)
  Haber: Inventarios (1435) por SUM(item.cost_at_time × item.quantity)

  Nota: si SUM(cost) = 0, este asiento no se genera.
```

**`purchase_invoice` (posted):**
```
Asiento — Compra a crédito:
  Debe:  Inventarios (1435) por invoice.total
  Haber: CxP (2205) por invoice.total
```

**`payment` (payable_type='accounts_receivable' — cobro):**
```
Asiento — Cobro de cartera:
  Debe:  Caja/Banco del cash_account → cuenta del product code del cash_account
  Haber: CxC (1305) por payment.amount
```

**`payment` (payable_type='accounts_payable' — pago a proveedor):**
```
Asiento — Cancelación de deuda:
  Debe:  CxP (2205) por payment.amount
  Haber: Caja/Banco del cash_account → cuenta del product code del cash_account
```

**`transfer`:**
```
Asiento — Traslado de fondos:
  Debe:  Caja/Banco de to_account por amount
  Haber: Caja/Banco de from_account por amount
```

### 8.3 Resolución del código de cuenta del cash_account

`cash_accounts` necesita saber qué cuenta contable le corresponde (ej. Caja → 1105, Banco → 1110).
Se resuelve así:

**Propuesta:** agregar un campo `account_id` (FK chart_of_accounts, nullable) a `cash_accounts`.
Al no estar configurado, AccountingService lanza una excepción descriptiva.

Esta es una configuración que el usuario hace en la pantalla de Cuentas de efectivo.

### 8.4 Resolución del código de cuenta para productos

Los campos `inventory_account_code`, `cogs_account_code`, `sale_account_code` en `products` ya
existen como VARCHAR(20). AccountingService los usa para resolver las cuentas al generar el asiento
de una factura de venta o compra.

**Propuesta alternativa y más correcta:** en lugar de resolver por código, agregar FKs directas:
- `products.inventory_account_id` → FK `chart_of_accounts`
- `products.cogs_account_id` → FK `chart_of_accounts`
- `products.sale_account_id` → FK `chart_of_accounts`

Esto reemplaza las columnas `*_account_code` existentes (que son VARCHAR y requieren búsqueda extra).

⚠️ **DECISIÓN PENDIENTE D-CON-6:** ¿mantener los *_account_code VARCHAR existentes y buscar por código,
o migrar a FKs directas? Los FKs son más seguros e indexables pero requieren una migración que
modifica `products`.

---

## 9. Cierre contable

### 9.1 Verificaciones previas al cierre

Antes de ejecutar `closePeriod()`, AccountingService verifica:
1. No existen asientos en estado `draft` dentro del período.
2. Todos los documentos del período están conciliados (opcional en MVP, puede ser warning).
3. El usuario tiene permiso `accounting.close`.

### 9.2 Proceso de cierre

```
1. Calcular resultado del período:
   resultado = SUM(revenue.credit) + SUM(cost/expense.credit)
             - SUM(revenue.debit)  - SUM(cost/expense.debit)

2. Generar asientos de cierre (reference_type='closing'):

   a) Cerrar ingresos:
      Debe: cada cuenta de ingresos por su saldo
      Haber: Resultado del ejercicio (3605/3610)

   b) Cerrar costos y gastos:
      Debe: Resultado del ejercicio (3605/3610)
      Haber: cada cuenta de costo/gasto por su saldo

3. Marcar período: status='closed', closed_by, closed_at.
```

### 9.3 Inicio del período siguiente

El siguiente período se inicia con cuentas de balance en cero. Los saldos de activos/pasivos/patrimonio
se cargan con un **asiento de apertura** (`reference_type='opening'`) al inicio del período.

⚠️ **DECISIÓN PENDIENTE D-CON-7:** ¿el asiento de apertura es automático (generado por closePeriod)
o manual (el contador lo crea)? Recomendación: **manual** para el MVP — el contador revisa los saldos
antes de abrir el nuevo período.

---

## 10. Reversiones

### 10.1 Regla absoluta

Un asiento `posted` **nunca se modifica ni se elimina**. La corrección es siempre una reversión:
se crea un nuevo asiento espejo (débitos y créditos invertidos) que neutraliza el efecto del original.

### 10.2 `reverseEntry(JournalEntry $entry, int $userId): JournalEntry`

```
1. Verifica: $entry.status === 'posted' (no reversiona un draft ni una reversión)
2. Verifica: $entry.reversed_by_entry_id IS NULL (no reversiona lo ya revertido)
3. Encuentra el período OPEN que cubre la fecha actual
4. Crea nuevo JournalEntry:
     - date = today (fecha de la reversión, en el período actual)
     - reference_type = 'reversal'
     - reference_id = $entry.id
     - reversal_of_entry_id = $entry.id
     - status = 'draft'
5. Crea las líneas con débitos/créditos invertidos
6. Postea el nuevo asiento
7. Marca $entry: reversed_by_entry_id = nuevo_asiento.id, status = 'reversed'
8. Limpia los ganchos del documento origen (journal_entry_id → null, posted_at → null)
   para que el módulo pueda emitir un nuevo asiento si es necesario.
```

### 10.3 Reversión en período cerrado

Si el asiento original está en un período `closed`:
- La reversión se crea en el período `open` actual (fecha = today).
- No se reabre el período cerrado.
- El número del asiento de reversión pertenece al período actual.

⚠️ **DECISIÓN PENDIENTE D-CON-8:** ¿Permitir la reversión de asientos de un período cerrado sin
reabrirlo? **Recomendación: SÍ** — es la práctica estándar en contabilidad y no compromete la
integridad del período cerrado (la reversión va en el período actual, no en el cerrado).

---

## 11. Trazabilidad completa

La cadena completa de un cobro de factura de venta puede reconstruirse así:

```
journal_entry (id=50, reference_type='payment', reference_id=12)
     ↓ (buscar payment #12)
payment (id=12, payable_type='accounts_receivable', payable_id=7)
     ↓ (buscar accounts_receivable #7)
accounts_receivable (id=7, sale_invoice_id=3)
     ↓ (buscar sale_invoice #3)
sale_invoice (id=3, journal_entry_id=48)  ← el asiento de la factura
     ↓ (buscar journal_entry #48)
journal_entry (id=48, reference_type='sale_invoice', reference_id=3)
     ↓ (ver sus líneas)
journal_entry_lines: Debe CxC, Haber Ingresos + Debe COGS, Haber Inventario
```

Para el movimiento de inventario:
```
journal_entry (id=48, reference_type='sale_invoice', reference_id=3)
     ↓ (buscar stock_movements con reference_type='sale_invoice', reference_id=3)
stock_movements (journal_entry_id=48, posted_at=...)
     ↓ (cierra el círculo)
```

---

## 12. Ganchos en documentos existentes — propuesta de nuevas columnas

Para completar la trazabilidad bidireccional, se propone agregar `journal_entry_id` (FK nullable)
a las siguientes tablas con una **migración exclusiva de Fase F** (no modifica lógica existente):

| Tabla | Campo a agregar | Propósito |
|-------|----------------|-----------|
| `sale_invoices` | `journal_entry_id` (unsignedBigInteger nullable) | Asiento de la factura de venta |
| `purchase_invoices` | `journal_entry_id` (unsignedBigInteger nullable) | Asiento de la factura de compra |
| `accounts_receivable` | (no nuevo — trazabilidad vía sale_invoice) | — |
| `accounts_payable` | (no nuevo — trazabilidad vía purchase_invoice) | — |
| `transfers` | `journal_entry_id` (unsignedBigInteger nullable) | Asiento de la transferencia |
| `cash_accounts` | `account_id` FK chart_of_accounts nullable | Cuenta contable de esta caja |

Los campos `payments.journal_entry_id` y `stock_movements.journal_entry_id` ya existen.

⚠️ **DECISIÓN PENDIENTE D-CON-9:** ¿agregar FK a `accounts_payable` y `accounts_receivable` también,
o es suficiente con llegar vía purchase/sale_invoice? La cadena vía invoice es suficiente para la
mayoría de los casos. Solo agregar si se necesita en un reporte específico.

---

## 13. Cuentas de integración — tabla de configuración

Para resolver qué cuenta contable usar en cada tipo de asiento automático, se propone una tabla de
configuración por empresa:

```
accounting_account_configs
  id
  company_id            FK companies
  config_key            varchar(60)  — ver lista abajo
  account_id            FK chart_of_accounts
  timestamps
  UNIQUE (company_id, config_key)
```

**Claves de configuración predefinidas:**

| config_key | Descripción | Cuenta típica |
|------------|-------------|---------------|
| `cxc_default` | CxC por defecto (clientes) | 1305 |
| `cxp_default` | CxP por defecto (proveedores) | 2205 |
| `inventory_default` | Inventario de mercancías | 1435 |
| `cogs_default` | Costo de ventas | 6135 |
| `revenue_default` | Ingresos por ventas | 4135 |
| `retained_earnings` | Utilidades acumuladas | 3705 |
| `net_income` | Resultado del ejercicio | 3605 |

La resolución es: si el producto tiene `inventory_account_id` configurado, se usa ese.
Si no, se usa el `config_key` genérico de la empresa.
Si tampoco existe, AccountingService lanza excepción descriptiva.

---

## 14. Multitenancy

Todas las tablas nuevas llevan `company_id`. Las consultas siempre hacen scope por `company_id`.

**Verificaciones en AccountingService:**
- El período pertenece a la misma empresa que el asiento.
- Cada cuenta en las líneas pertenece a la misma empresa.
- Los documentos referenciados pertenecen a la misma empresa.
- Un usuario no puede ver/crear asientos de otra empresa (ResolvesCompany via controllers).

---

## 15. Auditoría

Todas las siguientes operaciones se registran con `AuditService::record()`:

| Operación | audit_action |
|-----------|-------------|
| Crear cuenta contable | `chart_of_account_created` |
| Modificar cuenta contable | `chart_of_account_updated` |
| Inactivar cuenta | `chart_of_account_deactivated` |
| Abrir período | `accounting_period_opened` |
| Cerrar período | `accounting_period_closed` |
| Crear asiento (draft) | `journal_entry_created` |
| Postear asiento | `journal_entry_posted` |
| Revertir asiento | `journal_entry_reversed` |
| Asiento de apertura | `journal_entry_opening` |
| Asientos de cierre | `journal_entry_closing` |

El BaseCrudController ya hace auditoría automática para CRUD de ChartOfAccount y AccountingPeriod.
Las operaciones especiales de AccountingService llaman a AuditService manualmente.

---

## 16. Permisos

Propuesta de 4 permisos (guard='web', via migración `add_accounting_permissions`):

| Permiso | Descripción | Rol sugerido |
|---------|-------------|-------------|
| `accounting.view` | Ver plan de cuentas, períodos, asientos y reportes | Administrador, Contador |
| `accounting.manage` | Crear/editar cuentas, crear asientos manuales | Administrador, Contador |
| `accounting.post` | Postear asientos draft | Administrador, Contador senior |
| `accounting.close` | Cerrar períodos contables | Solo Super Admin, Administrador |

**Análisis de necesidad:**
- `accounting.view` vs `accounting.manage`: necesarios — ver los libros ≠ modificarlos.
- `accounting.post`: útil si se quiere que un rol cree drafts pero otro los apruebe. En MVP
  puede fusionarse con `accounting.manage`.
- `accounting.close`: crítico tenerlo separado — el cierre es irreversible.

⚠️ **DECISIÓN PENDIENTE D-CON-10:** ¿mantener 4 permisos o fusionar `manage` + `post` en uno?
Recomendación: **mantener los 4** — es más barato definirlos ahora que agregar un permiso después
cuando ya hay usuarios configurados.

---

## 17. Reportes (diseño conceptual — no implementar en Fase F MVP)

| Reporte | Origen de datos | Filtros |
|---------|----------------|---------|
| Balance de comprobación | `journal_entry_lines` agrupado por `account_id` | período, fecha rango, cuenta |
| Libro diario | `journal_entries` + líneas | período, referencia, fecha |
| Mayor (libro mayor) | `journal_entry_lines` agrupado y ordenado por cuenta | cuenta, fecha |
| Estado de resultados | Cuentas tipo revenue/cost/expense | período |
| Balance general | Cuentas tipo asset/liability/equity | período (saldos acumulados) |
| CxC por vencer | `accounts_receivable` | empresa, fecha |
| CxP por vencer | `accounts_payable` | empresa, fecha |
| Movimientos por cuenta | `journal_entry_lines` de una cuenta específica | cuenta, período |

**Todos se calculan on-the-fly** desde `journal_entry_lines` de asientos `posted`.
No hay tabla de saldos precalculados en el MVP — si el volumen lo requiere, se añade en Fase 9 (Optimización).

---

## 18. Integridad — mecanismos preventivos

| Riesgo | Mecanismo |
|--------|-----------|
| Doble contabilización del mismo documento | `UNIQUE (company_id, reference_type, reference_id)` en `journal_entries` — solo un asiento por referencia. Para facturas con dos asientos (ingreso + COGS), se usa un `journal_entry_group_id` o dos entradas con `reference_type` distintos ('sale_invoice_income' / 'sale_invoice_cogs'). ⚠️ DECISIÓN PENDIENTE D-CON-11 |
| Asiento descuadrado (sum_debit ≠ sum_credit) | Verificación en `postEntry()` antes de cambiar status a posted. Si falla → excepción, rollback. |
| Cuenta inactiva en asiento | Verificación en `postEntry()`: todas las cuentas de las líneas deben ser active + allows_movements=true. |
| Asiento en período cerrado | `generateAndPost()` busca el período open que contiene la fecha; si no hay período open → excepción. |
| Cuenta de otra empresa | `account.company_id === entry.company_id` verificado en AccountingService. |
| Reversión duplicada | `entry.reversed_by_entry_id IS NULL` antes de revertir. |
| Asiento de apertura duplicado | Una sola entrada `reference_type='opening'` por período (UNIQUE en accounting_periods). ⚠️ DECISIÓN PENDIENTE D-CON-12: implementar o dejar como regla de negocio. |
| Cierre de período con drafts pendientes | AccountingService verifica `journal_entries.status='draft'` antes de cerrar. |
| Eliminación de cuenta con movimientos | FK `RESTRICT` en `journal_entry_lines.account_id`. No se puede eliminar una cuenta que tiene líneas. |

---

## 19. Colombia — aspectos normativos a considerar

⚠️ **TODO lo de esta sección requiere validación normativa** con un contador colombiano
o revisión de la norma. No implementar como reglas técnicas sin confirmar.

| Aspecto | Norma | Estado |
|---------|-------|--------|
| Plan de cuentas (PUC) | Decreto 2649/1993 y 2650/1993 | ⚠️ VALIDAR NORMATIVAMENTE |
| NIIF para PYMES | Decreto 3022/2013 | ⚠️ VALIDAR si aplica al segmento objetivo |
| Numeración de comprobantes | DIAN — libros contables firmados | ⚠️ VALIDAR |
| IVA (19%, 5%, exento) | Estatuto Tributario | ⚠️ Actualmente los documentos tienen campo `tax` sin tipo. Posible gap. |
| Retención en la fuente | Código de Comercio | ⚠️ No modelado aún |
| Renta presuntiva | DIAN | ⚠️ Fuera del scope del ERP por ahora |
| Firma digital de libros | DIAN resolución | ⚠️ VALIDAR si es obligatorio |
| Conservación de registros | Código de Comercio (art. 60) — 10 años | ⚠️ Ningún registro debe borrarse definitivamente |

**Lo que SÍ está cubierto arquitectónicamente:**
- Los registros nunca se borran (compensaciones, no deletes).
- Trazabilidad completa de cada asiento hasta el documento origen.
- Numeración secuencial de asientos por empresa.
- Períodos contables con cierre formal.

---

## 20. Riesgos identificados

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|-----------|
| AccountingService hace la llamada dentro de la transacción de PaymentService → si falla el asiento, revierte también el pago | Baja (el asiento debería cuadrar si el pago es válido) | Alto | En MVP: aceptar este comportamiento. Documentar como decisión. |
| Período no abierto cuando se postea una factura | Media (al inicio del ERP) | Alto | Verificación explícita + excepción descriptiva con instrucción al usuario. |
| Plan de cuentas no configurado → asientos no generados | Alta (al inicio) | Medio | AccountingService no falla silenciosamente — lanza excepción visible. |
| Accounts de integración mal configuradas → asientos incorrectos | Media | Alto | UI de configuración con vista previa del asiento antes de guardar (Fase F.2). |
| Volumen de journal_entry_lines en empresas grandes | Baja (MVP) | Medio | Sin precalculado por ahora; índices adecuados; revisar en Fase 9. |
| Gap D-CON-11 (doble contabilización) | Media si no se resuelve antes del código | Alto | Resolver antes de escribir AccountingService. |

---

## 21. Lo que NO se toca

- `BaseCrudController` — sin modificaciones.
- `StockService` — sin modificaciones. AccountingService LLAMA a StockService (no al revés).
- `PaymentService` — se le añade UNA llamada a AccountingService al final de cada operación. No se modifica su lógica.
- `PurchaseService` / `SaleService` — ídem anterior.
- `ModuleTablePage` / `DataTable` / `CrudModal` — sin modificaciones. El frontend de Contabilidad usa el mismo patrón.
- Tablas de RRHH, CRM — intactas, no tienen relevancia contable en el MVP.
- Autenticación / Sanctum / Spatie — sin modificaciones.

---

## 22. Decisiones arquitectónicas — resumen

### Decisiones propuestas (pendientes de aprobación)

| ID | Decisión | Recomendación |
|----|----------|--------------|
| D-CON-1 | Trigger mechanism: llamada directa vs. Laravel Events | Llamada directa dentro de la transacción del servicio origen |
| D-CON-2 | Tabla de configuración `accounting_account_configs` vs. campos en `companies` | Tabla separada (más flexible, permite múltiples claves) |
| D-CON-3 | `draft` + `postEntry` separados vs. auto-post en MVP | Auto-post en MVP (simplifica flujo); exponer draft cuando haya caso de uso real |
| D-CON-4 | Dos asientos por factura de venta (ingreso + COGS) vs. uno con 4 líneas | Un asiento con 4 líneas (más limpio, una transacción) |
| D-CON-5 | Seed del plan de cuentas: ¿genérico o completo PUC? | Estructura básica funcional; PUC completo como seeder opcional |

### Decisiones pendientes (⚠️ resolver antes de implementar)

| ID | Pregunta | Opciones | Recomendación |
|----|----------|----------|--------------|
| D-CON-6 | `*_account_code` VARCHAR vs. FK directas en `products` | FKs directas (más seguras) vs. conservar VARCHAR | **FKs directas** — requiere migración de products |
| D-CON-7 | Asiento de apertura: automático al cerrar período vs. manual | Manual (MVP) vs. automático | **Manual para MVP** |
| D-CON-8 | Reversión de asientos en período cerrado | Revertir en período actual vs. bloquear | **Revertir en período actual** |
| D-CON-9 | `journal_entry_id` en `accounts_payable` y `accounts_receivable` | Agregar vs. dejar sin FK (trazabilidad vía invoice) | **Dejar sin FK** — trazabilidad vía invoice es suficiente |
| D-CON-10 | 4 permisos vs. fusionar `manage` + `post` | 4 separados vs. 3 | **4 separados** |
| D-CON-11 | Doble asiento por factura de venta | Un asiento 4 líneas vs. dos asientos | **Un asiento 4 líneas** (más simple, un UNIQUE funciona) |
| D-CON-12 | Idempotencia del asiento de apertura | UNIQUE en BD vs. verificación en servicio | **Verificación en servicio** (más descriptivo) |

---

## 23. Orden de implementación sugerido (Fase F por capas)

```
Capa 1: BD
  - Migración de permisos (accounting.view/manage/post/close)
  - Migración de tablas (chart_of_accounts, accounting_periods, journal_entries, journal_entry_lines)
  - Migración de ganchos en documentos existentes (sale_invoices.journal_entry_id, purchase_invoices.journal_entry_id, transfers.journal_entry_id, cash_accounts.account_id)
  - Migración de FKs en products (si se aprueba D-CON-6)
  → Verificar: migrate:fresh --seed sin errores

Capa 2: Modelos
  - ChartOfAccount, AccountingPeriod, JournalEntry, JournalEntryLine
  - AccountingAccountConfig
  - Relaciones, casts, fillables
  → No hay tests de modelos (los tests de servicio los cubren)

Capa 3: AccountingService (el núcleo)
  - Métodos: generateAndPost, postEntry, reverseEntry, openPeriod, closePeriod, createOpeningEntry
  - Tests: AccountingServiceTest (similar en profundidad a FinanceTest — ~25 tests)
  → Verificar: php artisan test --filter=AccountingServiceTest

Capa 4: FormRequests, Resources, Controllers
  - StoreChartOfAccountRequest, StoreAccountingPeriodRequest, StoreAccountingAccountConfigRequest
  - Resources para todas las entidades
  - ChartOfAccountController, AccountingPeriodController, JournalEntryController (read-only), AccountingAccountConfigController
  - Rutas en api.php

Capa 5: Integrar AccountingService en servicios existentes
  - PurchaseService::postInvoice → llamada a AccountingService
  - SaleService::postInvoice → llamada a AccountingService
  - PaymentService::registerPayment/cancelPayment/transfer/cancelTransfer → llamadas a AccountingService
  → Tests de regresión: PurchasesTest + SalesTest + FinanceTest deben seguir pasando
  → Tests nuevos: AccountingIntegrationTest (flujos end-to-end)

Capa 6: Frontend
  - /app/contabilidad/plan-cuentas (árbol jerárquico — custom page, no ModuleTablePage)
  - /app/contabilidad/configuracion (tabla accounting_account_configs)
  - /app/contabilidad/periodos (CRUD con acciones abrir/cerrar)
  - /app/contabilidad/diario (lista read-only de journal_entries con líneas)
  - Navegación en admin-shell.tsx

  → Verificar: npm run build verde

Capa 7: Reportes (puede ir después del MVP)
  - Balance de comprobación
  - Libro diario
  - Estado de resultados básico
```

---

## 24. Criterio de DONE para Fase F

```
[ ] migrate:fresh --seed sin errores
[ ] AccountingService: generateAndPost, postEntry, reverseEntry, openPeriod, closePeriod implementados
[ ] Tests AccountingServiceTest: flujos principales, partida doble, período cerrado, reversión
[ ] Tests de regresión: PurchasesTest + SalesTest + FinanceTest siguen pasando (42/42)
[ ] PurchaseService/SaleService/PaymentService integrados con AccountingService
[ ] Frontend: plan-cuentas, periodos, diario, configuración, nav
[ ] Permisos: 4 permisos asignados a roles correctos
[ ] Ganchos rellenos: payments.journal_entry_id, stock_movements.journal_entry_id/posted_at
[ ] Trazabilidad verificada: de un payment → journal_entry → sale_invoice → stock_movement
[ ] npm run build verde sin errores TypeScript
[ ] Decisiones D-CON-6 a D-CON-12 resueltas e implementadas
```
