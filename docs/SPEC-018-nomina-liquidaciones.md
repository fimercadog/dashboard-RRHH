# SPEC-018 — Nómina y Liquidaciones

> Estado: BORRADOR v3 — ajustes Fidel incorporados, pendiente revisión final de PEPE
> Autor: SPEC-PLANNER (FidelOS)
> Fecha revisión: 2026-10-20
> Rama base: `master` · HEAD: `71ea559`
> Cambios v2: D-001..D-005 incorporadas; B-1 valores 2026 parametrizados; B-2 fórmulas corregidas con casos de prueba; B-3 indemnización por tipo de contrato; B-4 retención en la fuente con procedimiento completo.
> Cambios v3: DP-001 — tabla `company_tax_profiles` reemplaza campo enum en `companies`; UVT 2026 = $52.374 (Resolución DIAN 000238 de 2025), verificada 2026-10-20; sección 11 — tabla VP-001..VP-006 con controles explícitos de bloqueo; CA-N — cierre de nómina bloqueado si faltan parámetros obligatorios; `payroll_runs.tax_profile_id` FK a `company_tax_profiles`; casos de prueba de retención actualizados con UVT verificada.

---

## Índice

1. [Inventario del código existente](#1-inventario-del-código-existente)
2. [Mapa de procesos y dependencias](#2-mapa-de-procesos-y-dependencias)
3. [Propuesta de MVP y fases](#3-propuesta-de-mvp-y-fases)
4. [Modelo de datos y contratos API propuestos](#4-modelo-de-datos-y-contratos-api-propuestos)
5. [Diseño de parámetros, vigencias y versiones](#5-diseño-de-parámetros-vigencias-y-versiones)
6. [Reglas de cálculo con fuentes y supuestos](#6-reglas-de-cálculo-con-fuentes-y-supuestos)
7. [Criterios de aceptación y matriz de pruebas](#7-criterios-de-aceptación-y-matriz-de-pruebas)
8. [Riesgos, brechas y decisiones pendientes](#8-riesgos-brechas-y-decisiones-pendientes)
9. [Decisiones arquitectónicas incorporadas (D-001..D-005)](#9-decisiones-arquitectónicas-incorporadas)
10. [Decisiones pendientes de validación profesional](#10-decisiones-pendientes-de-validación-profesional)
11. [Validaciones profesionales pendientes y controles de bloqueo](#11-validaciones-profesionales-pendientes-y-controles-de-bloqueo)

---

## 1. Inventario del código existente

### 1.1 Qué existe y es reutilizable

| Tabla / Modelo | Archivo de migración | Relevancia para nómina |
|---|---|---|
| `employees` | `2026_08_24_044359_create_employees_table.php` | Fuente de `salary`, `contract_type`, `hire_date`, `termination_date`, `employment_status` |
| `employees` (SS) | `2026_10_20_100001_add_social_security_to_employees_table.php` | `eps`, `arl`, `pension_fund`, `compensation_fund` — entidades del empleado |
| `employee_work_history` | `2026_10_20_100002_create_employee_work_history_table.php` | Historial de ajustes salariales (`event_type = salary_adjustment`) |
| `attendances` | `2026_08_24_044400_create_attendances_table.php` | `check_in`, `check_out`, `date`, `late_minutes`, `status` |
| `vacation_requests` | `2026_08_24_044400_create_vacation_requests_table.php` | `start_date`, `end_date`, `requested_days`, `status` |
| `sick_leaves` | `2026_08_24_044401_create_sick_leaves_table.php` | `start_date`, `end_date`, `days`, `type`, `status` |
| `permission_requests` | `2026_08_24_044400_create_permission_requests_table.php` | `start_date`, `end_date`, `requested_days`, `type`, `status` |
| `shifts` | `2026_08_24_044402_create_shifts_table.php` | `start_time`, `end_time`, `break_minutes` |
| `BaseCrudController` | `app/Http/Controllers/Api/BaseCrudController.php` | Patrón estándar — todos los controllers nuevos lo extienden |
| `ResolvesCompany` | `app/Http/Controllers/Api/Concerns/ResolvesCompany.php` | `company_id` siempre del token |
| `AuditService` | `app/Services/AuditService.php` | Auditoría de acciones — usar en operaciones de nómina |

**Nota D-002:** Las tablas `accounting_periods`, `journal_entries`, `journal_entry_lines`, `chart_of_accounts` y `accounting_account_configs` NO son dependencia de la Fase A. El asiento contable automático va a la Fase B. La nómina funciona sin plan de cuentas.

### 1.2 Qué NO existe (brechas confirmadas)

Ninguno de los siguientes existe en el repositorio a la fecha de esta auditoría:

| Elemento ausente | Impacto |
|---|---|
| Tabla `employee_contracts` | Sin historial de contratos no hay base temporal para calcular prestaciones |
| Tabla `payroll_periods` | No hay concepto de período de nómina |
| Tabla `payroll_runs` | No hay ejecuciones de nómina |
| Tabla `payroll_items` | No hay líneas de devengos/deducciones por empleado por período |
| Tabla `payroll_parameters` / `payroll_parameter_values` | Sin esta tabla, las tasas legales irían hardcodeadas en el código |
| Tabla `payroll_novelties` | Sin novedades formales no se pueden registrar conceptos variables |
| Tabla `social_security_contributions` | Sin esto no se puede generar planilla PILA |
| Tabla `severance_accruals` (cesantías) | Sin esta tabla el cálculo de prestaciones es ad hoc |
| Columna de horas extras en `attendances` | KI-001: no hay campo para horas extra; solo `late_minutes` |
| `PayrollController` | Inexistente |
| `PayrollPeriodController` | Inexistente |
| Permisos `payroll.*` | No registrados en ninguna migración |

### 1.3 Parcialmente implementado

| Elemento | Estado parcial | Brecha |
|---|---|---|
| `employees.contract_type` | String libre (`fijo`, `indefinido`, etc.) | No es FK; no tiene fechas de vigencia; no hay tabla de contratos |
| `employees.salary` | `decimal(12,2)` — salario actual | No hay historial de salarios por contrato; `employee_work_history` tiene `salary_adjustment` como evento pero no guarda el monto |
| `sick_leaves.type` | String — tipo de incapacidad | No distingue entre incapacidad de origen común (EPS paga) vs laboral (ARL paga) con impacto en nómina |

---

## 2. Mapa de procesos y dependencias

```
DATOS DE ENTRADA
│
├── Employee (salary, contract_type, hire_date, SS entities)
├── EmployeeContract (salario del contrato vigente, fechas, tipo)
├── Attendance (asistencia, tardanzas, horas registradas)
├── VacationRequest (vacaciones aprobadas en el período)
├── SickLeave (incapacidades en el período)
├── PermissionRequest (permisos en el período)
├── PayrollParameters (tasas legales vigentes — a crear)
└── PayrollNovelties (bonos, descuentos, embargos — a crear)
                │
                ▼
        PROCESAMIENTO DE NÓMINA
        PayrollRun (período, estado, totales)
                │
                ▼
        PayrollItems (una fila por empleado por período)
        ├── Devengos: salario base, horas extra, bonificaciones,
        │            auxilio de transporte, vacaciones disfrutadas
        └── Deducciones: salud empleado, pensión empleado,
                         préstamos, embargos, otros descuentos
                │
                ▼
        APORTES PATRONALES (calculados pero NO deducidos del empleado)
        └── Salud empleador, Pensión empleador, ARL,
            Caja compensación, Parafiscales (SENA, ICBF — cuando aplica)
                │
                ▼
        PRESTACIONES SOCIALES (acumuladas por período, Fase B)
        ├── Prima de servicios
        ├── Cesantías + Intereses cesantías
        └── Vacaciones (causadas)
                │
                ├── COMPROBANTE DE NÓMINA (recibo por empleado)
                ├── PLANILLA PILA (aportes SS — Fase D)
                └── ASIENTO CONTABLE (Fase B — requiere plan de cuentas configurado)
```

### 2.1 Dependencias externas confirmadas

| Módulo externo | Dato requerido | Estado |
|---|---|---|
| `employees` | `salary`, `hire_date`, `contract_type`, SS entities | Existe |
| `employee_contracts` | salario del contrato, fechas vigencia, tipo | A crear (Fase A) |
| `attendances` | días trabajados, tardanzas | Existe — falta horas extra |
| `vacation_requests` | días aprobados en período | Existe |
| `sick_leaves` | días por tipo y origen | Existe — falta distinción origen laboral/común |
| `permission_requests` | días con/sin pago | Existe — falta campo `is_paid` |
| `accounting_periods` | NO es dependencia Fase A | Existe (se usa en Fase B) |
| `chart_of_accounts` | NO es dependencia Fase A | Existe (se usa en Fase B) |

---

## 3. Propuesta de MVP y fases

### Entrega inicial — MVP (Fase A)

Procesamiento de nómina mensual para empleados con salario fijo, contrato indefinido o fijo, sin horas extra, sin comisiones variables. Produce recibo de nómina.

**Incluye:**
- Tabla `employee_contracts` (contrato formal con fechas)
- Tabla `payroll_parameters` + `payroll_parameter_values` (parámetros legales con vigencia y fuente normativa)
- Tabla `payroll_periods` (períodos de nómina, **independientes** de `accounting_periods`)
- Tabla `payroll_runs` (ejecución por período)
- Tabla `payroll_items` (líneas por empleado)
- Tabla `payroll_item_lines` (detalle auditable de cada concepto)
- Tabla `payroll_novelties` (novedades manuales por empleado por período)
- Cálculo de devengos básicos: salario base, auxilio de transporte
- Cálculo de deducciones básicas: salud 4%, pensión 4%
- Cálculo de aportes patronales: salud 8.5%, pensión 12%, ARL, caja, parafiscales (cuando aplica según evaluación D-004)
- Integración de novedades aprobadas del período (vacaciones, incapacidades, permisos)
- Recibo de nómina (PDF o vista imprimible)
- Permisos RBAC `payroll.*`

**No incluye en MVP:**
- Asiento contable automático (Fase B — requiere plan de cuentas; no bloquea la nómina)
- Horas extra (bloqueado por KI-001 — requiere decisión)
- Prestaciones sociales automáticas (acumulados y liquidación — Fase B)
- Liquidación final al terminar contrato (Fase C)
- Generación de archivo PILA (Fase D)
- Portal del empleado (Fase D)

### Fase B — Contabilidad y prestaciones sociales

- Asiento contable automático al cerrar el período (requiere `accounting_account_configs` configuradas)
- Acumulados de prima de servicios, cesantías, intereses cesantías, vacaciones causadas
- Vista de provisiones por empleado
- Liquidación de prestaciones semestrales/anuales

### Fase C — Liquidación final

- Cálculo de liquidación al terminar contrato
- Integración con `termination_date` en `employees`
- Comprobante de liquidación
- Cálculo de indemnización por tipo de contrato (ver §6.14)

### Fase D — Funcionalidades avanzadas

- Generación de archivo PILA (requiere validación con operador PILA)
- Portal del empleado (autoservicio — FUERA DEL ALCANCE según PROJECT_STATE.md §9)
- Horas extra (depende de resolución KI-001)
- Comisiones y variables

### Dependencias entre fases

```
Fase A (MVP) → requerida por todas las fases posteriores
Fase B → requiere Fase A (necesita payroll_items históricos para calcular promedios; requiere plan de cuentas)
Fase C → requiere Fase A + Fase B (liquidación usa acumulados de prestaciones)
Fase D → requiere Fase A + Fase B + Fase C
```

---

## 4. Modelo de datos y contratos API propuestos

### 4.1 Tabla `employee_contracts`

Fuente de verdad para todos los cálculos de nómina. `employees.salary` y `employees.contract_type` se sincronizan como campos de lectura rápida (desnormalizados) pero **no** son la fuente de verdad para nómina.

```sql
CREATE TABLE employee_contracts (
    id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id        BIGINT UNSIGNED NOT NULL REFERENCES companies(id),
    employee_id       BIGINT UNSIGNED NOT NULL REFERENCES employees(id),
    contract_number   VARCHAR(50) NOT NULL,
    contract_type     ENUM('indefinido','fijo','obra_labor','aprendizaje','prestacion_servicios')
                      NOT NULL,
    salary_modality   ENUM('ordinary','integral') NOT NULL DEFAULT 'ordinary',
    -- ordinary = salario ordinario con todos los beneficios de ley
    -- integral = salario integral (>= 10 SMMLV; factor prestacional incluido)
    start_date        DATE NOT NULL,
    end_date          DATE NULL,         -- NULL = indefinido
    salary            DECIMAL(12,2) NOT NULL,
    salary_type       ENUM('monthly','daily','hourly') NOT NULL DEFAULT 'monthly',
    work_hours_per_week TINYINT UNSIGNED NOT NULL DEFAULT 48,
    risk_class        TINYINT UNSIGNED NOT NULL DEFAULT 1,  -- ARL clase 1-5
    status            ENUM('active','terminated','suspended') NOT NULL DEFAULT 'active',
    termination_reason VARCHAR(200) NULL,
    terminated_at     DATE NULL,
    notes             TEXT NULL,
    created_by        BIGINT UNSIGNED REFERENCES users(id),
    created_at        TIMESTAMP,
    updated_at        TIMESTAMP,
    deleted_at        TIMESTAMP NULL,

    UNIQUE KEY (company_id, contract_number),
    INDEX (company_id, employee_id),
    INDEX (company_id, status),
    INDEX (company_id, start_date)
);
```

**Trazabilidad:** todo cambio de salario o condiciones genera un nuevo registro de contrato (o un nuevo contrato), no una actualización en lugar. El historial de contratos queda completo. La tabla `employee_work_history` puede registrar el evento `contract_change` apuntando al nuevo `employee_contracts.id`.

### 4.2 Tabla `payroll_parameters`

```sql
CREATE TABLE payroll_parameters (
    id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    code        VARCHAR(60) NOT NULL,    -- ej. 'smmlv', 'auxilio_transporte'
    name        VARCHAR(150) NOT NULL,
    description TEXT NULL,
    scope       ENUM('global','company') NOT NULL DEFAULT 'global',
    company_id  BIGINT UNSIGNED NULL REFERENCES companies(id),
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP,

    UNIQUE KEY (code, company_id)  -- NULL company_id = global
);
```

### 4.3 Tabla `payroll_parameter_values`

```sql
CREATE TABLE payroll_parameter_values (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    parameter_id    BIGINT UNSIGNED NOT NULL REFERENCES payroll_parameters(id),
    effective_from  DATE NOT NULL,
    effective_to    DATE NULL,          -- NULL = vigente indefinidamente
    value           DECIMAL(14,4) NOT NULL,
    unit            VARCHAR(30) NOT NULL,   -- 'COP', 'percent', 'days', 'uvt'
    source_norm     VARCHAR(300) NOT NULL,  -- ej. 'Decreto 159 de 2026 Art. 1'
    source_url      VARCHAR(500) NULL,      -- URL oficial si existe
    verified_at     DATE NULL,             -- fecha de verificación manual
    verified_by     BIGINT UNSIGNED NULL REFERENCES users(id),
    notes           TEXT NULL,
    created_by      BIGINT UNSIGNED REFERENCES users(id),
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,

    INDEX (parameter_id, effective_from),
    INDEX (effective_from, effective_to)
);
```

**Regla de inmutabilidad:** nunca se actualiza un `payroll_parameter_values` existente. Al cambiar un valor se crea un nuevo registro con nueva `effective_from` y se cierra el anterior (`effective_to = nueva_effective_from - 1 día`).

### 4.4 Tabla `payroll_periods`

Los períodos de nómina son **independientes** de `accounting_periods` (D-002). No se requiere un período contable abierto para crear ni cerrar un período de nómina.

```sql
CREATE TABLE payroll_periods (
    id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id    BIGINT UNSIGNED NOT NULL REFERENCES companies(id),
    name          VARCHAR(100) NOT NULL,   -- ej. 'Nómina Octubre 2026'
    period_type   ENUM('monthly','biweekly','weekly') NOT NULL DEFAULT 'monthly',
    start_date    DATE NOT NULL,
    end_date      DATE NOT NULL,
    payment_date  DATE NOT NULL,
    status        ENUM('draft','processing','closed','cancelled') NOT NULL DEFAULT 'draft',
    -- Nóminas cerradas son INMUTABLES (D-003)
    -- Correcciones: crear payroll_period de tipo complementaria vinculado a este id
    -- Anulaciones: requieren authorized_reversal = true + reversal_reason
    is_complementary       BOOLEAN NOT NULL DEFAULT FALSE,
    original_period_id     BIGINT UNSIGNED NULL REFERENCES payroll_periods(id),
    authorized_reversal    BOOLEAN NOT NULL DEFAULT FALSE,
    reversal_reason        VARCHAR(500) NULL,
    reversal_authorized_by BIGINT UNSIGNED NULL REFERENCES users(id),
    reversal_authorized_at TIMESTAMP NULL,
    closed_by     BIGINT UNSIGNED NULL REFERENCES users(id),
    closed_at     TIMESTAMP NULL,
    notes         TEXT NULL,
    created_at    TIMESTAMP,
    updated_at    TIMESTAMP,

    INDEX (company_id, status),
    INDEX (company_id, start_date, end_date)
);
```

### 4.5 Tabla `payroll_runs`

```sql
CREATE TABLE payroll_runs (
    id                    BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id            BIGINT UNSIGNED NOT NULL REFERENCES companies(id),
    payroll_period_id     BIGINT UNSIGNED NOT NULL REFERENCES payroll_periods(id),
    run_number            TINYINT UNSIGNED NOT NULL DEFAULT 1,
    status                ENUM('pending','running','completed','failed') NOT NULL DEFAULT 'pending',
    total_employees       SMALLINT UNSIGNED DEFAULT 0,
    total_devengado       DECIMAL(14,2) DEFAULT 0,
    total_deducciones     DECIMAL(14,2) DEFAULT 0,
    total_neto            DECIMAL(14,2) DEFAULT 0,
    total_aportes_empresa DECIMAL(14,2) DEFAULT 0,
    parameters_snapshot   JSON NOT NULL,   -- snapshot de TODOS los parámetros usados
    -- El snapshot incluye: código, nombre, valor, unidad, source_norm, vigencia usada
    tax_profile_id        BIGINT UNSIGNED NULL REFERENCES company_tax_profiles(id),
    -- FK al perfil tributario validado activo al momento del cálculo.
    -- Se establece al cerrar el período y es INMUTABLE post-cierre (D-003).
    -- No puede ser NULL en un run completado si el período está cerrado.
    error_log             TEXT NULL,
    executed_by           BIGINT UNSIGNED REFERENCES users(id),
    executed_at           TIMESTAMP NULL,
    created_at            TIMESTAMP,
    updated_at            TIMESTAMP,

    INDEX (company_id, payroll_period_id),
    INDEX (company_id, status)
);
```

### 4.5b — Tabla `company_tax_profiles`

Perfil tributario estructurado de la empresa. Reemplaza el campo `payroll_tax_regime` enum que se había propuesto en `companies` (DP-001). Permite versionar cambios de naturaleza jurídica y registrar quién validó la configuración.

**Regla de elegibilidad de exoneración parafiscal:** la exoneración del Art. 114-1 ET no depende solo de `parafiscal_exempt = true`. El sistema también verifica que el salario del trabajador sea menor a 10 SMMLV. Si `review_status != 'validated'`, el sistema no activa la exoneración aunque el campo indique `true`.

```sql
CREATE TABLE company_tax_profiles (
    id                     BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id             BIGINT UNSIGNED NOT NULL REFERENCES companies(id),
    legal_nature           VARCHAR(100) NOT NULL,
    -- Ejemplos: 'SAS', 'SRL', 'ESAL', 'persona_natural', 'cooperativa', 'entidad_publica'
    tax_contributor_type   VARCHAR(100) NOT NULL,
    -- Ejemplos: 'gran_contribuyente', 'regimen_simple', 'no_contribuyente', 'contribuyente_ordinario'
    parafiscal_exempt      BOOLEAN NOT NULL DEFAULT FALSE,
    -- TRUE si la empresa califica para la exoneración del Art. 114-1 ET.
    -- El sistema NUNCA activa la exoneración si review_status != 'validated'.
    exempt_basis           TEXT NULL,
    -- Descripción de los criterios verificados (naturaleza jurídica, obligación de declarar renta).
    verified_by            VARCHAR(200) NULL,
    -- Nombre del contador público o abogado laboral que validó esta configuración.
    verified_at            DATE NULL,
    review_status          ENUM('pending','validated','requires_professional_review')
                           NOT NULL DEFAULT 'pending',
    -- pending: ingresado pero no revisado por un profesional.
    -- validated: un profesional confirmó la configuración; la exoneración puede activarse.
    -- requires_professional_review: hay inconsistencias que deben resolverse antes de usar.
    valid_from             DATE NOT NULL,
    -- Inicio de vigencia de este perfil tributario.
    valid_until            DATE NULL,
    -- Fin de vigencia. NULL = vigente indefinidamente.
    created_by             BIGINT UNSIGNED REFERENCES users(id),
    updated_by             BIGINT UNSIGNED REFERENCES users(id),
    created_at             TIMESTAMP,
    updated_at             TIMESTAMP,

    INDEX (company_id, valid_from),
    -- Lookup por fecha de período: WHERE company_id = ? AND valid_from <= :fecha
    --   AND (valid_until IS NULL OR valid_until >= :fecha)
    INDEX (company_id, review_status)
);
```

**Trazabilidad:** no se actualiza un registro existente; cada cambio de naturaleza jurídica crea un nuevo perfil con nueva `valid_from` y cierra el anterior (`valid_until = nueva_valid_from - 1 día`).

**Criterio de aceptación derivado (CA-N y §11):** una nómina NO puede cerrarse si la empresa no tiene un `company_tax_profile` con `review_status = 'validated'` vigente para la fecha del período.

---

### 4.6 Tabla `payroll_items`

```sql
CREATE TABLE payroll_items (
    id                      BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id              BIGINT UNSIGNED NOT NULL REFERENCES companies(id),
    payroll_run_id          BIGINT UNSIGNED NOT NULL REFERENCES payroll_runs(id),
    employee_id             BIGINT UNSIGNED NOT NULL REFERENCES employees(id),
    contract_id             BIGINT UNSIGNED NOT NULL REFERENCES employee_contracts(id),

    -- Snapshot del contrato al momento del cálculo
    salary_snapshot         DECIMAL(12,2) NOT NULL,
    salary_modality_snapshot ENUM('ordinary','integral') NOT NULL,
    ibc                     DECIMAL(12,2) NOT NULL,

    -- Días trabajados
    worked_days             DECIMAL(5,2) NOT NULL,
    vacation_days           DECIMAL(5,2) DEFAULT 0,
    sick_days_common        DECIMAL(5,2) DEFAULT 0,
    sick_days_labor         DECIMAL(5,2) DEFAULT 0,
    unpaid_days             DECIMAL(5,2) DEFAULT 0,

    -- Devengados (COP)
    salary_earned           DECIMAL(12,2) NOT NULL,
    transport_allowance     DECIMAL(10,2) DEFAULT 0,
    vacation_pay            DECIMAL(10,2) DEFAULT 0,
    overtime_pay            DECIMAL(10,2) DEFAULT 0,  -- Fase D
    bonuses                 DECIMAL(10,2) DEFAULT 0,
    other_earnings          DECIMAL(10,2) DEFAULT 0,
    total_earned            DECIMAL(14,2) NOT NULL,

    -- Deducciones empleado
    health_employee         DECIMAL(10,2) NOT NULL,
    pension_employee        DECIMAL(10,2) NOT NULL,
    solidarity_fund         DECIMAL(10,2) DEFAULT 0,
    withholding_tax         DECIMAL(10,2) DEFAULT 0,  -- retención en la fuente
    loan_deductions         DECIMAL(10,2) DEFAULT 0,
    other_deductions        DECIMAL(10,2) DEFAULT 0,
    total_deductions        DECIMAL(14,2) NOT NULL,

    -- Neto
    net_pay                 DECIMAL(14,2) NOT NULL,

    -- Aportes empresa
    health_employer         DECIMAL(10,2) NOT NULL,
    pension_employer        DECIMAL(10,2) NOT NULL,
    arl_employer            DECIMAL(10,2) NOT NULL,
    ccf_employer            DECIMAL(10,2) NOT NULL,
    sena_employer           DECIMAL(10,2) DEFAULT 0,
    icbf_employer           DECIMAL(10,2) DEFAULT 0,
    parafiscal_exempt       BOOLEAN NOT NULL DEFAULT FALSE,
    parafiscal_exempt_basis VARCHAR(300) NULL,  -- criterio legal aplicado (D-004)
    total_employer_cost     DECIMAL(14,2) NOT NULL,

    -- Provisiones del período (Fase B — columnas presentes, calculadas en Fase B)
    severance_provision     DECIMAL(10,2) DEFAULT 0,
    interest_provision      DECIMAL(10,2) DEFAULT 0,
    prima_provision         DECIMAL(10,2) DEFAULT 0,
    vacation_provision      DECIMAL(10,2) DEFAULT 0,

    status                  ENUM('calculated','approved','paid') NOT NULL DEFAULT 'calculated',
    notes                   TEXT NULL,
    created_at              TIMESTAMP,
    updated_at              TIMESTAMP,

    UNIQUE KEY (payroll_run_id, employee_id),
    INDEX (company_id, payroll_run_id),
    INDEX (company_id, employee_id)
);
```

### 4.7 Tabla `payroll_item_lines`

```sql
CREATE TABLE payroll_item_lines (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    payroll_item_id BIGINT UNSIGNED NOT NULL REFERENCES payroll_items(id),
    line_type       ENUM('earning','deduction','employer_contribution','provision') NOT NULL,
    code            VARCHAR(60) NOT NULL,
    description     VARCHAR(200) NOT NULL,
    quantity        DECIMAL(8,4) NULL,
    unit_value      DECIMAL(12,4) NULL,
    amount          DECIMAL(14,2) NOT NULL,
    source_type     VARCHAR(50) NULL,
    source_id       BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP,

    INDEX (payroll_item_id, line_type)
);
```

### 4.8 Tabla `payroll_novelties`

```sql
CREATE TABLE payroll_novelties (
    id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id        BIGINT UNSIGNED NOT NULL REFERENCES companies(id),
    payroll_period_id BIGINT UNSIGNED NOT NULL REFERENCES payroll_periods(id),
    employee_id       BIGINT UNSIGNED NOT NULL REFERENCES employees(id),
    novelty_type      VARCHAR(60) NOT NULL,
    description       VARCHAR(200) NOT NULL,
    amount            DECIMAL(12,2) NOT NULL,
    is_recurrent      BOOLEAN DEFAULT FALSE,
    applies_ibc       BOOLEAN DEFAULT FALSE,
    status            ENUM('pending','applied','rejected') NOT NULL DEFAULT 'pending',
    applied_run_id    BIGINT UNSIGNED NULL REFERENCES payroll_runs(id),
    approved_by       BIGINT UNSIGNED NULL REFERENCES users(id),
    approved_at       TIMESTAMP NULL,
    notes             TEXT NULL,
    created_by        BIGINT UNSIGNED REFERENCES users(id),
    created_at        TIMESTAMP,
    updated_at        TIMESTAMP,

    INDEX (company_id, payroll_period_id, employee_id),
    INDEX (company_id, status)
);
```

### 4.9 Contratos API del MVP

#### Contratos

| Método | Ruta | Auth / Gate | Descripción |
|---|---|---|---|
| GET | `/api/employees/{id}/contracts` | `auth:sanctum`, `can:payroll.manage` | Listar contratos del empleado |
| POST | `/api/employees/{id}/contracts` | `auth:sanctum`, `can:payroll.manage` | Crear contrato |
| GET | `/api/contracts/{id}` | `auth:sanctum`, `can:payroll.manage` | Ver contrato |
| PUT | `/api/contracts/{id}` | `auth:sanctum`, `can:payroll.manage` | Editar contrato (solo si está activo) |
| POST | `/api/contracts/{id}/terminate` | `auth:sanctum`, `can:payroll.delete` | Terminar contrato |

#### Parámetros

| Método | Ruta | Auth / Gate | Descripción |
|---|---|---|---|
| GET | `/api/payroll/parameters` | `auth:sanctum`, `can:payroll.manage` | Listar parámetros y valores vigentes |
| POST | `/api/payroll/parameters/{id}/values` | `auth:sanctum`, `can:payroll.manage` | Agregar nuevo valor con vigencia |
| GET | `/api/payroll/parameters/{id}/history` | `auth:sanctum`, `can:payroll.manage` | Historial de vigencias |

#### Períodos de nómina

| Método | Ruta | Auth / Gate | Descripción |
|---|---|---|---|
| GET | `/api/payroll/periods` | `auth:sanctum`, `can:payroll.view` | Listar períodos |
| POST | `/api/payroll/periods` | `auth:sanctum`, `can:payroll.manage` | Crear período |
| GET | `/api/payroll/periods/{id}` | `auth:sanctum`, `can:payroll.view` | Ver período |
| POST | `/api/payroll/periods/{id}/close` | `auth:sanctum`, `can:payroll.close` | Cerrar período (irreversible sin autorización explícita) |
| POST | `/api/payroll/periods/{id}/complementary` | `auth:sanctum`, `can:payroll.manage` | Crear período complementario vinculado |
| POST | `/api/payroll/periods/{id}/reverse` | `auth:sanctum`, `can:payroll.close` | Reversar período cerrado (requiere motivo + usuario autorizado) |

#### Ejecución de nómina

| Método | Ruta | Auth / Gate | Descripción |
|---|---|---|---|
| POST | `/api/payroll/periods/{id}/run` | `auth:sanctum`, `can:payroll.manage` | Calcular/re-calcular nómina |
| GET | `/api/payroll/runs/{id}` | `auth:sanctum`, `can:payroll.view` | Ver resultado de la ejecución |
| GET | `/api/payroll/runs/{id}/items` | `auth:sanctum`, `can:payroll.view` | Listar ítems de la ejecución |
| GET | `/api/payroll/items/{id}/voucher` | `auth:sanctum`, `can:payroll.view` | Recibo de nómina (PDF/HTML) |
| POST | `/api/payroll/runs/{id}/approve` | `auth:sanctum`, `can:payroll.close` | Aprobar ejecución |

#### Novedades

| Método | Ruta | Auth / Gate | Descripción |
|---|---|---|---|
| GET | `/api/payroll/periods/{id}/novelties` | `auth:sanctum`, `can:payroll.manage` | Listar novedades del período |
| POST | `/api/payroll/periods/{id}/novelties` | `auth:sanctum`, `can:payroll.manage` | Agregar novedad |
| PUT | `/api/payroll/novelties/{id}` | `auth:sanctum`, `can:payroll.manage` | Editar novedad (solo período en draft) |
| DELETE | `/api/payroll/novelties/{id}` | `auth:sanctum`, `can:payroll.manage` | Eliminar novedad (solo período en draft) |

---

## 5. Diseño de parámetros, vigencias y versiones

### 5.1 Catálogo inicial de parámetros globales

Todos los valores legales se almacenan en `payroll_parameter_values` con `effective_from`, `effective_to`, `source_norm` y `source_url`. **Ningún valor legal se hardcodea en código ni en migraciones de datos.**

Los valores indicados a continuación son los vigentes para 2026 según los decretos citados. El sistema los carga como datos de seed; cualquier actualización futura crea un nuevo registro.

| Código | Nombre | Unidad | Valor 2026 | Fuente normativa |
|---|---|---|---|---|
| `smmlv` | Salario mínimo mensual legal vigente | COP | $1.750.905 | Decreto 159 de 2026, Art. 1 |
| `auxilio_transporte` | Auxilio de transporte | COP | $249.095 | Decreto 1470 de 2025, Art. 1 |
| `salud_empleado_pct` | Aporte salud empleado | percent | 4.00 | Art. 204 Ley 100/1993 |
| `salud_empleador_pct` | Aporte salud empleador | percent | 8.50 | Art. 204 Ley 100/1993 |
| `pension_empleado_pct` | Aporte pensión empleado | percent | 4.00 | Art. 7 Ley 797/2003 |
| `pension_empleador_pct` | Aporte pensión empleador | percent | 12.00 | Art. 7 Ley 797/2003 |
| `fondo_solidaridad_umbral_smmlv` | IBC mínimo para fondo solidaridad | SMMLV | 4.00 | Art. 27 Ley 100/1993 |
| `ccf_pct` | Caja de compensación familiar | percent | 4.00 | Ley 21/1982 |
| `sena_pct` | Aportes SENA | percent | 2.00 | Ley 21/1982 |
| `icbf_pct` | Aportes ICBF | percent | 3.00 | Ley 21/1982 |
| `arl_clase_1_pct` | ARL clase de riesgo I (mínima de rango) | percent | 0.348 | Decreto 1607/2002 |
| `arl_clase_2_pct` | ARL clase de riesgo II | percent | 0.435 | Decreto 1607/2002 |
| `arl_clase_3_pct` | ARL clase de riesgo III | percent | 0.783 | Decreto 1607/2002 |
| `arl_clase_4_pct` | ARL clase de riesgo IV | percent | 1.044 | Decreto 1607/2002 |
| `arl_clase_5_pct` | ARL clase de riesgo V | percent | 6.960 | Decreto 1607/2002 |
| `tope_auxilio_transporte_smmlv` | Tope salarial para auxilio transporte | SMMLV | 2.00 | Ley 15/1959, actualizado anualmente |
| `ibc_maximo_smmlv` | IBC máximo | SMMLV | 25.00 | Art. 5 Ley 797/2003 |
| `uvt` | Unidad de Valor Tributario | COP | $52.374 | **Resolución DIAN 000238 de 2025** · `verified_at = '2026-10-20'` |
| `retefuente_renta_exenta_pct` | Porcentaje renta exenta empleados (Art. 206 ET) | percent | 25.00 | Numeral 10 Art. 206 ET |
| `retefuente_renta_exenta_tope_uvt` | Tope mensual renta exenta | UVT | 240 | Numeral 10 Art. 206 ET |

> **Advertencia sobre ARL:** Los porcentajes indicados son las tasas mínimas de cada clase. La tasa exacta la asigna la ARL según la actividad económica del empleador. El sistema usa la tasa del parámetro como base; la empresa puede crear un parámetro de `scope = 'company'` con la tasa real asignada por su ARL.

> **UVT 2026 verificada:** La UVT para 2026 es **$52.374**, fijada por la **Resolución DIAN 000238 de 2025**. El seed de datos debe cargar este valor con `key = 'uvt'`, `value = '52374'`, `currency = 'COP'`, `valid_from = '2026-01-01'`, `source_norm = 'Resolución DIAN 000238 de 2025'`, `verified_at = '2026-10-20'`. Este parámetro está **resuelto** (VP-001 — ver §11).

### 5.2 Cómo se versiona

- Cada `payroll_parameter_values` tiene `effective_from` y `effective_to`.
- Al crear un nuevo valor, el anterior se cierra: `effective_to = effective_from_nuevo - 1 día`.
- Para buscar el valor vigente en una fecha dada:
  ```sql
  SELECT value FROM payroll_parameter_values
  WHERE parameter_id = ?
    AND effective_from <= :fecha
    AND (effective_to IS NULL OR effective_to >= :fecha)
  LIMIT 1
  ```
- **Snapshot obligatorio:** al crear un `payroll_run`, el servicio guarda en `parameters_snapshot` (JSON) todos los valores utilizados, incluyendo `source_norm` y `effective_from`/`effective_to`. Así, al revisar un período histórico, siempre se puede auditar qué parámetros y qué fuente normativa se usaron.

### 5.3 Servicio `PayrollParameterService`

```php
// Pseudocódigo — no implementar hasta aprobación SPEC
class PayrollParameterService {
    public function getValueAt(string $code, Carbon $date, ?int $companyId = null): float;
    // Busca primero en scope='company' para el companyId dado;
    // si no hay, cae al parámetro global.

    public function getSnapshotForPeriod(Carbon $from, Carbon $to, ?int $companyId = null): array;
    // Devuelve todos los parámetros vigentes durante el período,
    // incluyendo source_norm, verified_at y el valor.
}
```

### 5.4 Autorización de cambios de parámetros

- Solo `payroll.manage` puede crear nuevos valores de parámetros de empresa.
- Parámetros globales (scope='global') solo los puede crear/modificar `Super Admin`.
- Todo cambio queda auditado en `audit_logs` vía `AuditService`.

### 5.5 Inmutabilidad de períodos cerrados (D-003)

Una vez `payroll_periods.status = 'closed'`, los registros asociados (`payroll_runs`, `payroll_items`, `payroll_item_lines`) son **inmutables**. Está prohibido modificarlos.

**Correcciones ordinarias** → crear un período de tipo `is_complementary = TRUE` vinculado al período original (`original_period_id`). El período complementario sigue el mismo flujo que un período normal.

**Anulación completa** → solo cuando no hay otro mecanismo. Requiere:
1. Usuario con permiso `payroll.close`.
2. Campo `reversal_reason` obligatorio (mínimo 50 caracteres).
3. Registro en `audit_logs` con el usuario, el motivo y la fecha.
4. El período queda en `status = 'cancelled'` y se crea un registro compensatorio.
5. **Nunca** se eliminan `payroll_items` ni `payroll_item_lines` históricos.

El middleware de cierre verifica el estado antes de cualquier mutación:
```php
// En PayrollService — antes de cualquier escritura sobre payroll_items
if ($period->status === 'closed' && !$isAuthorizedReversal) {
    abort(422, 'El período de nómina está cerrado. Use nómina complementaria o solicite autorización de reversión.');
}
```

---

## 6. Reglas de cálculo con fuentes y supuestos

> **ADVERTENCIA LEGAL**: Las reglas descritas en esta sección son una guía de diseño basada en la legislación colombiana vigente al corte de conocimiento de este documento. **Deben ser verificadas por un contador público o abogado laboral antes de usar el módulo en producción.** El sistema permite actualizar todos los valores sin cambiar código (ver §5). Esta sección NO es un concepto contable ni un concepto jurídico.

---

### 6.1 Ingreso Base de Cotización (IBC)

**Base legal:** Arts. 127 y 128 CST; Art. 5 Ley 797/2003.
**Fuente verificada:** Código Sustantivo del Trabajo (CST) Colombia.

```
IBC = salario_devengado_mensual
    + pagos habitualmente recibidos y constitutivos de salario
      (bonos habituales, horas extra recurrentes — Fase D)
    - auxilio_de_transporte (excluido del IBC por disposición expresa)
    - pagos en especie no constitutivos (Art. 128 CST)

Mínimo IBC = parámetro 'smmlv'
Máximo IBC = parámetro 'ibc_maximo_smmlv' × parámetro 'smmlv'  (= 25 SMMLV)
```

**Caso especial salario integral:** Para contratos con `salary_modality = 'integral'`, el IBC es el 70% del salario integral. El 30% restante es el factor prestacional (no cotiza ni genera prestaciones de ley en la proporción normal).

---

### 6.2 Auxilio de transporte

**Base legal:** Ley 15/1959; actualizado anualmente por decreto.
**Fuente verificada:** Decreto 1470 de 2025.

```
Condición: salary_snapshot <= parámetro 'tope_auxilio_transporte_smmlv' × parámetro 'smmlv'
           = salary_snapshot <= 2 × $1.750.905 = $3.501.810 (2026)
Monto: parámetro 'auxilio_transporte' vigente al inicio del período ($249.095 en 2026)
```

**El auxilio de transporte NO forma parte del IBC pero SÍ es base de cesantías y prima** (ver §6.6, §6.8).

---

### 6.3 Aportes en salud (EPS)

**Base legal:** Art. 204 Ley 100/1993.

```
Empleado: IBC × parámetro 'salud_empleado_pct'   (4%)
Empleador: IBC × parámetro 'salud_empleador_pct'  (8.5%)
Total sistema: IBC × 12.5%
```

**Exoneración empleador salud (Art. 114-1 ET, Ley 1819/2016):** los empleadores sujetos al impuesto de renta (personas jurídicas contribuyentes) están exonerados del aporte patronal a salud para trabajadores que devenguen menos de 10 SMMLV. Esta exoneración se evalúa igual que la de parafiscales (D-004). El campo `parafiscal_exempt` en `payroll_items` aplica también a esta exoneración y el campo `parafiscal_exempt_basis` registra el criterio.

---

### 6.4 Aportes en pensión

**Base legal:** Art. 7 Ley 797/2003.

```
Empleado: IBC × parámetro 'pension_empleado_pct'  (4%)
Empleador: IBC × parámetro 'pension_empleador_pct' (12%)

Fondo solidaridad pensional (solo empleados con IBC > 4 SMMLV):
  - Entre 4 y 16 SMMLV: +1% adicional a cargo del empleado
  - Entre 16 y 17 SMMLV: +1.2%
  - Entre 17 y 18 SMMLV: +1.4%
  - Entre 18 y 19 SMMLV: +1.6%
  - Entre 19 y 20 SMMLV: +1.8%
  - Más de 20 SMMLV: +2%
```

**Fuente:** Art. 27 Ley 100/1993; Art. 7 Ley 797/2003.
**REQUIERE VERIFICACIÓN:** la escala del fondo de solidaridad debe ser confirmada con un contador para los tramos exactos vigentes en 2026.

---

### 6.5 ARL

**Base legal:** Decreto 1607/2002.

```
Empleador paga el 100%
Tasa según parámetro 'arl_clase_N_pct' donde N = risk_class del contrato
```

**REQUIERE VERIFICACIÓN:** Las tasas del parámetro son las mínimas del rango de cada clase. La tasa exacta la asigna la ARL según la actividad económica del empleador (CIIU). Se recomienda crear un parámetro de scope='company' con la tasa real asignada por la ARL contratada.

---

### 6.6 Caja de compensación familiar (CCF)

**Base legal:** Ley 21/1982.

```
Empleador paga: IBC × parámetro 'ccf_pct'  (4%)
```

---

### 6.7 Parafiscales — SENA e ICBF (D-004)

**Base legal:** Ley 21/1982; Arts. 25 y 114-1 ET (modificado por Ley 1607/2012 y Ley 1819/2016).
**Fuente verificada:** Estatuto Tributario Colombia, Art. 114-1.

**La exención de SENA (2%) e ICBF (3%) NO es automática y no depende únicamente del número de empleados.** El sistema evalúa las condiciones legales según Art. 114-1 ET:

**Condiciones para la exoneración:**
1. El empleador debe ser persona jurídica o asimilada contribuyente declarante del impuesto sobre la renta (incluye sociedades extranjeras con actividad en Colombia).
2. La exoneración aplica para cada trabajador que devengue **menos de 10 SMMLV**.
3. **No aplica** para: entidades del sector público, entidades sin ánimo de lucro (ESAL), personas naturales empleadoras, contribuyentes del régimen simple.

**Diseño del sistema (D-004):**
- El sistema evalúa las condiciones descritas y registra el criterio aplicado en `payroll_items.parafiscal_exempt_basis`.
- No simplifica a "< N empleados" — evalúa tipo de empleador y nivel salarial por trabajador.
- El resultado puede ser revisado por usuario con permiso `payroll.manage` y la revisión queda en `audit_logs`.
- En la UI se muestra al administrador el criterio aplicado y la fuente normativa.

```
Si empleador es persona jurídica contribuyente de renta
  Y trabajador devengue < 10 × smmlv:
    SENA: $0 (exento)
    ICBF: $0 (exento)
    parafiscal_exempt = TRUE
    parafiscal_exempt_basis = 'Art. 114-1 ET — PJ contribuyente, salario < 10 SMMLV'
Sino:
    SENA: IBC × parámetro 'sena_pct'  (2%)
    ICBF: IBC × parámetro 'icbf_pct'  (3%)
    parafiscal_exempt = FALSE
```

**REQUIERE VALIDACIÓN PROFESIONAL:** Ver §10 — la naturaleza jurídica de la empresa cliente debe verificarse con el contador o abogado antes de activar la exoneración.

---

### 6.8 Cesantías

**Base legal:** Art. 249 CST.
**Fuente verificada:** Código Sustantivo del Trabajo, Art. 249.

**Distinción entre tipos de valor:**
- **Provisión mensual:** lo que se acumula contablemente cada mes (Fase B).
- **Valor causado en el período:** lo que corresponde al período de nómina calculado.
- **Valor efectivamente pagadero:** lo que se desembolsa al fondo o al trabajador (anual al 14 de febrero, o al terminar el contrato).

**Fórmula:**
```
Cesantías = (salario_base + auxilio_transporte_si_aplica) × días_trabajados / 360
```

**Nota:** El auxilio de transporte **SÍ** se incluye en la base de cesantías (y prima). NO se incluye en la base de vacaciones.

#### Caso de prueba — Cesantías

**Escenario A: Salario mínimo, auxilio de transporte aplica, mes completo (30 días)**
- Input: salary = $1.750.905, auxilio_transporte = $249.095, días = 30
- Base: $1.750.905 + $249.095 = $2.000.000
- Cálculo: $2.000.000 × 30 / 360
- Paso a paso: $2.000.000 × 0.08333... = $166.666,67
- Resultado esperado: **$166.666,67** (provisión mensual)

**Escenario B: Salario $3.000.000, sin auxilio transporte (salario > 2 SMMLV), mes completo**
- Input: salary = $3.000.000, auxilio_transporte = $0, días = 30
- Base: $3.000.000
- Cálculo: $3.000.000 × 30 / 360 = $250.000
- Resultado esperado: **$250.000** (provisión mensual)

**Escenario C: Fracción de mes — empleado ingresa el día 15, trabaja 16 días**
- Input: salary = $3.000.000, días = 16
- Cálculo: $3.000.000 × 16 / 360 = $133.333,33
- Resultado esperado: **$133.333,33**

---

### 6.9 Intereses sobre cesantías

**Base legal:** Ley 52/1975; Art. 99 Ley 50/1990.
**Fuente verificada:** Ley 52 de 1975.

**Fórmula:**
```
Intereses_cesantías = saldo_cesantías_acumuladas × 12% × días_del_período / 360
```

**Distinción:** Los intereses se pagan sobre el saldo acumulado de cesantías al 31 de diciembre (o al terminar el contrato), no sobre la provisión mensual.

#### Caso de prueba — Intereses sobre cesantías

**Escenario: Saldo cesantías al 31 diciembre = $2.000.000 (año completo de 360 días)**
- Input: saldo_cesantías = $2.000.000, días = 360, tasa = 12%
- Cálculo: $2.000.000 × 0.12 × 360 / 360
- Paso a paso: $2.000.000 × 0.12 × 1 = $240.000
- Resultado esperado: **$240.000**

**Escenario B: Provisión mensual de intereses (30 días sobre acumulado del año)**
- Input: saldo_cesantías_acumulado_a_la_fecha = $1.000.000, días = 30
- Cálculo: $1.000.000 × 0.12 × 30 / 360 = $10.000
- Resultado esperado: **$10.000**

---

### 6.10 Prima de servicios

**Base legal:** Art. 306 CST.
**Fuente verificada:** Código Sustantivo del Trabajo, Art. 306.

**La fórmula es idéntica a cesantías en cuanto a la base (salario + auxilio de transporte si aplica).**

**Fórmula:**
```
Prima = (salario_base + auxilio_transporte_si_aplica) × días_trabajados / 360
```

**Periodicidad de pago:** mitad antes del 30 de junio y mitad antes del 20 de diciembre. El sistema calcula la provisión mensual.

#### Caso de prueba — Prima

**Escenario A: Salario mínimo, semestre completo (180 días)**
- Input: salary = $1.750.905, auxilio = $249.095, días = 180
- Base: $2.000.000
- Cálculo: $2.000.000 × 180 / 360 = $1.000.000
- Resultado esperado: **$1.000.000**

**Escenario B: Provisión mensual (30 días)**
- Input: salary = $3.000.000, auxilio = $0, días = 30
- Cálculo: $3.000.000 × 30 / 360 = $250.000
- Resultado esperado: **$250.000**

---

### 6.11 Vacaciones

**Base legal:** Art. 186 CST.
**Fuente verificada:** Código Sustantivo del Trabajo, Art. 186 — 15 días hábiles de vacaciones por año de servicio.

**Diferencia clave respecto a cesantías y prima:** el auxilio de transporte **NO** forma parte de la base de vacaciones.

**Fórmula:**
```
Vacaciones = salario_base × días_trabajados / 720
```

El divisor 720 equivale a 15 días por año / 360 días: un año de 360 días genera 15 días de vacaciones (= 360/24 = 15). La fórmula es equivalente a `salario × 15 / 360 × (días/30)` simplificada.

#### Caso de prueba — Vacaciones

**Escenario A: Salario mínimo, año completo (360 días)**
- Input: salary = $1.750.905, días = 360
- Cálculo: $1.750.905 × 360 / 720 = $1.750.905 × 0.5
- Resultado esperado: **$875.452,50**

**Escenario B: Provisión mensual (30 días)**
- Input: salary = $3.000.000, días = 30
- Cálculo: $3.000.000 × 30 / 720 = $125.000
- Resultado esperado: **$125.000**

**Escenario C: Salario mínimo, provisión mensual**
- Input: salary = $1.750.905, días = 30
- Cálculo: $1.750.905 × 30 / 720 = $72.954,38
- Resultado esperado: **$72.954,38**
- Nota: El auxilio de transporte ($249.095) NO se suma, a diferencia de cesantías.

---

### 6.12 Novedades de asistencia que afectan el cálculo

| Tipo | Fuente | Efecto en nómina |
|---|---|---|
| Vacaciones aprobadas | `vacation_requests` | Días se cuentan como trabajados para IBC; se paga salario normal |
| Incapacidad de origen común (días 1-3) | `sick_leaves` | Empleador paga 66.67% del IBC |
| Incapacidad de origen común (días 4+) | `sick_leaves` | EPS paga 66.67% — descuento del devengado del empleado |
| Incapacidad de origen laboral | `sick_leaves` | ARL paga — descuento del devengado |
| Permiso remunerado | `permission_requests` donde `is_paid=true` | Sin descuento |
| Permiso no remunerado | `permission_requests` donde `is_paid=false` | Descuento proporcional |
| Ausencia injustificada | `attendances` con `status='absent'` | Descuento proporcional |

**Brecha activa:** `permission_requests` NO tiene columna `is_paid`. Debe agregarse como parte de la Fase A de esta SPEC. Ver §8.

---

### 6.13 Limitaciones y supuestos del MVP

- El MVP asume empleados a tiempo completo con salario fijo mensual.
- No calcula horas extra (depende de resolución KI-001).
- No calcula comisiones ni salario variable.
- No distingue contratos de aprendizaje (reglas distintas en SS).
- No maneja teletrabajo ni trabajo en casa con diferencias de ARL.
- La clase de riesgo ARL se toma de `employee_contracts.risk_class`.
- El cálculo de retención en la fuente (§6.15) está en MVP pero **requiere** que el parámetro UVT 2026 esté cargado y verificado.

---

### 6.14 Indemnización por terminación de contrato (Fase C)

**Base legal:** Art. 64 CST (modificado por Ley 789/2002, Art. 28).
**Fuente verificada:** Código Sustantivo del Trabajo, Art. 64.

> **IMPORTANTE:** Este cálculo corresponde a la **Fase C** (liquidación final). El MVP (Fase A) NO lo calcula. Se documenta aquí para que Juan y Nancy lo conozcan antes de implementar Fase C.

**La indemnización varía según el tipo de contrato y el nivel salarial. No existe una regla general única.**

#### Caso A: Contrato a término fijo

**Supuesto de hecho:** el empleador termina el contrato antes del vencimiento pactado, sin justa causa.

```
Indemnización = salario × días_restantes_hasta_vencimiento / 30
```

Si hay prórrogas automáticas o el empleador no avisa con 30 días de anticipación, aplican reglas adicionales que requieren revisión profesional.

#### Caso B: Contrato a término indefinido — salario < 10 SMMLV

**Art. 64 CST, Ley 789/2002:**

```
Si tiempo_servicio <= 1 año:
    Indemnización = 30 días de salario

Si tiempo_servicio > 1 año:
    Indemnización = 30 días de salario (primer año)
                  + 20 días de salario × años_adicionales
                  + 20 días × fracción_proporcional_de_año_en_curso
```

#### Caso de prueba — Contrato indefinido, salario < 10 SMMLV

- Input: salary = $3.000.000, fecha_inicio_contrato = 2023-01-01, fecha_terminación = 2026-10-20
- Tiempo de servicio: 3 años, 9 meses, 20 días ≈ 3 + (9×30 + 20)/360 = 3.888... años
- Fracción del año en curso: 9 meses 20 días = 290 días / 360 = 0.806 años
- Cálculo:
  - Primer año: 30 días × ($3.000.000/30) = $3.000.000
  - Años adicionales completos (2 años): 2 × 20 días × ($3.000.000/30) = 2 × $2.000.000 = $4.000.000
  - Fracción del cuarto año: 0.806 × 20 días × ($3.000.000/30) = 0.806 × $2.000.000 = $1.611.111
- Total: $3.000.000 + $4.000.000 + $1.611.111 = **$8.611.111**

#### Caso C: Contrato a término indefinido — salario ≥ 10 SMMLV

**Art. 64 CST, Ley 789/2002 — escala diferenciada:**

```
Si tiempo_servicio <= 1 año:
    Indemnización = 20 días de salario

Si tiempo_servicio > 1 año:
    Indemnización = 20 días de salario (primer año)
                  + 15 días de salario × años_adicionales
                  + 15 días × fracción_proporcional_de_año_en_curso
```

#### Caso D: Contrato por obra o labor

```
Indemnización = salario × días_estimados_restantes_de_obra / 30
```

La estimación de días restantes de obra es una determinación del empleador o del juez. **El sistema registra el valor declarado; no lo calcula automáticamente.**

#### Casos que requieren revisión profesional (el sistema NO calcula):

- Despido con justa causa invocada por el empleador (sin indemnización, pero requiere proceso disciplinario previo).
- Retiro voluntario del trabajador (sin indemnización ordinaria, salvo despido indirecto).
- Acuerdos de mutuo acuerdo con beneficios adicionales.
- Contratos especiales (aprendizaje, a domicilio, agentes comerciales).
- Despido colectivo (requiere autorización del Ministerio del Trabajo).

#### Indemnización moratoria — Art. 65 CST (el sistema NO calcula)

El Art. 65 CST establece una sanción por no pagar oportunamente las sumas adeudadas al terminar el contrato. Esta sanción es materia de litigio (corre desde la fecha de terminación, a razón de un día de salario por cada día de mora). **El sistema de nómina NO la calcula.** Es competencia del abogado laboral.

---

### 6.15 Retención en la fuente sobre salarios

**Base legal:** Arts. 383, 385, 386 y 206 ET (Estatuto Tributario); Decreto reglamentario.
**Fuente verificada:** Estatuto Tributario Colombia.

> **MVP soporta únicamente el Procedimiento 1 (Art. 385 ET)** — cálculo mensual sobre los ingresos del mes. El Procedimiento 2 (promedio de los últimos 12 meses, Art. 386 ET) queda para una versión posterior.

#### Procedimiento 1 — Depuración mensual

El procedimiento de retención en la fuente para empleados no es un umbral fijo sino un proceso de depuración en pasos:

```
PASO 1: Ingresos brutos del mes
        = salario_devengado + auxilio_transporte + bonificaciones + otros_ingresos

PASO 2: (-) Ingresos no constitutivos de renta ni ganancia ocasional
        = aportes_obligatorios_salud_empleado (4% IBC)
        + aportes_obligatorios_pensión_empleado (4% IBC)
        (Nota: el auxilio de transporte NO es ingreso constitutivo de salario
         para efectos tributarios — ya estaba fuera del IBC)

PASO 3: Ingresos netos
        = PASO 1 - PASO 2

PASO 4: (-) Deducciones (opcionales, deben ser certificadas por el trabajador):
        a) Intereses o corrección monetaria sobre préstamos para adquisición de
           vivienda o costo financiero leasing habitacional:
           hasta 100 UVT/mes
        b) Pagos por medicina prepagada del trabajador, cónyuge o dependientes:
           hasta 16 UVT/mes
        c) Deducción por dependientes:
           10% de los ingresos brutos del mes
           hasta 32 UVT/mes

PASO 5: Subtotal = PASO 3 - PASO 4

PASO 6: (-) Rentas exentas:
        a) Aportes voluntarios a fondos de pensiones obligatorios o AFC:
           hasta 30% del ingreso (tope conjunto con el siguiente numeral);
           tope anual 3.800 UVT (Art. 126-1 ET)
        b) Renta exenta del 25% (numeral 10, Art. 206 ET):
           25% del resultado de PASO 5 menos el literal a) del PASO 6
           tope: parámetro 'retefuente_renta_exenta_tope_uvt' × UVT/mes
                 = 240 UVT/mes (verificar valor UVT 2026)

PASO 7: Base gravable mensual
        = PASO 5 - PASO 6
        Si base_gravable <= 0: retención = $0

PASO 8: Aplicar tabla del Art. 383 ET sobre la base gravable en UVT
        base_gravable_uvt = base_gravable / parámetro 'uvt'

        Tabla Art. 383 ET (rangos en UVT):
          0    – 95 UVT:    0%
          95   – 150 UVT:   19% sobre el exceso de 95 UVT
          150  – 360 UVT:   28% sobre el exceso de 150 UVT + 10.5 UVT
          360  – 640 UVT:   33% sobre el exceso de 360 UVT + 69.3 UVT
          640  – 945 UVT:   35% sobre el exceso de 640 UVT + 162.1 UVT
          945  – 2300 UVT:  37% sobre el exceso de 945 UVT + 268.9 UVT
          > 2300 UVT:       39% sobre el exceso de 2.300 UVT + 770.0 UVT

        Retención del mes = resultado en UVT × parámetro 'uvt'
```

> **REQUIERE VERIFICACIÓN:** Los rangos y porcentajes de la tabla Art. 383 ET y los topes en UVT deben verificarse contra la versión del ET vigente para 2026 antes de implementar. El ET se actualiza periódicamente.

#### Caso de prueba — Retención en la fuente (Procedimiento 1)

**Escenario A: Salario $5.000.000 — sin deducciones ni rentas voluntarias**
- Input: salario_bruto = $5.000.000, IBC = $5.000.000, **UVT = $52.374** (Resolución DIAN 000238 de 2025)
- PASO 1: Ingresos brutos = $5.000.000
- PASO 2: Aportes obligatorios = ($5.000.000 × 4%) + ($5.000.000 × 4%) = $200.000 + $200.000 = $400.000
- PASO 3: Ingresos netos = $5.000.000 - $400.000 = $4.600.000
- PASO 4: Deducciones = $0 (sin certificados de dependientes ni vivienda)
- PASO 5: Subtotal = $4.600.000
- PASO 6: Renta exenta 25%: $4.600.000 × 25% = $1.150.000
  - Tope 240 UVT = 240 × $52.374 = $12.569.760 (no se supera)
  - Renta exenta aplicada = $1.150.000
- PASO 7: Base gravable = $4.600.000 - $1.150.000 = $3.450.000
- PASO 8: Base en UVT = $3.450.000 / $52.374 = **65.87 UVT**
  - Rango: 0 – 95 UVT → tasa = 0%
- Retención = **$0**

**Escenario B: Salario alto — $12.000.000**
- Input: salario_bruto = $12.000.000, **UVT = $52.374** (Resolución DIAN 000238 de 2025)
- PASO 1: $12.000.000
- PASO 2: $12.000.000 × 8% = $960.000 (salud + pensión empleado)
- PASO 3: $12.000.000 - $960.000 = $11.040.000
- PASO 4: Sin deducciones = $0
- PASO 5: $11.040.000
- PASO 6: Renta exenta 25% = $2.760.000; tope 240 UVT = $12.569.760 — no supera
- PASO 7: Base gravable = $11.040.000 - $2.760.000 = $8.280.000
- PASO 8: En UVT = $8.280.000 / $52.374 = **158.09 UVT**
  - Rango 150-360 UVT: 28% sobre exceso de 150 UVT + 10.5 UVT
  - Exceso = (158.09 - 150) = 8.09 UVT
  - Retención en UVT = 8.09 × 28% + 10.5 = 2.265 + 10.5 = 12.765 UVT
  - Retención en COP = 12.765 × $52.374 ≈ **$668.956**

> Estos cálculos usan la UVT 2026 verificada ($52.374, Resolución DIAN 000238 de 2025). Los rangos y porcentajes de la tabla Art. 383 ET deben ser confirmados por un contador antes de producción (VP-005 — ver §11).

---

## 7. Criterios de aceptación y matriz de pruebas

### 7.1 Contratos

**CA-001**
```
DADO un administrador con permiso payroll.manage
CUANDO crea un contrato para un empleado con salary=2.000.000, contract_type='fijo',
  salary_modality='ordinary', start_date='2026-01-01', end_date='2026-12-31', risk_class=1
ENTONCES el contrato queda en status='active'
  Y employees.salary se sincroniza a 2.000.000
```

**CA-002**
```
DADO un usuario sin permiso payroll.manage
CUANDO intenta crear un contrato
ENTONCES recibe HTTP 403
```

**CA-003**
```
DADO un empleado con company_id=1
CUANDO se intenta acceder a su contrato desde una sesión con company_id=2
ENTONCES recibe HTTP 404
```

### 7.2 Parámetros

**CA-004**
```
DADO que el parámetro 'smmlv' tiene valor 1.600.000 vigente hasta 2025-12-31
CUANDO se crea un nuevo valor 1.750.905 con effective_from='2026-01-01'
  Y source_norm='Decreto 159 de 2026 Art. 1'
ENTONCES el valor anterior queda con effective_to='2025-12-31'
  Y la consulta para fecha '2026-03-01' devuelve 1.750.905
  Y la consulta para fecha '2025-06-15' devuelve 1.600.000
```

**CA-005**
```
DADO que un payroll_run está en status='completed'
CUANDO se consulta su parameters_snapshot
ENTONCES contiene todos los parámetros usados con sus valores numéricos,
  source_norm, effective_from y effective_to
```

### 7.3 Procesamiento de nómina

**CA-006** (caso base — empleado con salario mínimo 2026, mes completo)
```
DADO un empleado con contrato salary=1.750.905 (= SMMLV 2026), risk_class=1,
  salary_modality='ordinary'
  Y el parámetro 'smmlv' en el sistema es 1.750.905 (Decreto 159/2026)
  Y el parámetro 'auxilio_transporte' es 249.095 (Decreto 1470/2025)
  Y el empleado trabajó 30 días completos en el período
CUANDO se ejecuta el payroll_run
ENTONCES:
  - salary_snapshot = 1.750.905
  - ibc = 1.750.905                             (auxilio no entra al IBC)
  - transport_allowance = 249.095               (1.750.905 <= 2 × 1.750.905)
  - total_earned = 1.750.905 + 249.095 = 2.000.000
  - health_employee = round(1.750.905 × 0.04) = 70.036
  - pension_employee = round(1.750.905 × 0.04) = 70.036
  - total_deductions = 140.072
  - net_pay = 2.000.000 - 140.072 = 1.859.928
  - health_employer = round(1.750.905 × 0.085) = 148.827
  - pension_employer = round(1.750.905 × 0.12) = 210.109
  - arl_employer = round(1.750.905 × 0.00348) = 6.093
  - ccf_employer = round(1.750.905 × 0.04) = 70.036
  - sena_employer = 0 (exento si empresa es PJ contribuyente y salario < 10 SMMLV)
  - icbf_employer = 0 (mismo criterio)
```

> Las cifras de CA-006 se basan en los parámetros del sistema, no en constantes hardcodeadas. El test de integración debe leer los parámetros y calcular los esperados dinámicamente, para que el test siga pasando si se actualizan los valores.

**CA-007** (período cerrado no modificable)
```
DADO un payroll_period en status='closed'
CUANDO se intenta ejecutar un nuevo run sobre ese período
ENTONCES recibe HTTP 422 con mensaje que incluye 'período de nómina está cerrado'
```

**CA-008** (aislamiento multiempresa)
```
DADO empresa con company_id=1 con 5 empleados
  Y empresa con company_id=2 con 3 empleados
CUANDO empresa 1 ejecuta su payroll_run
ENTONCES los payroll_items generados solo incluyen empleados de company_id=1
  Y consultar los payroll_items de la empresa 2 desde sesión empresa 1 devuelve HTTP 404
```

**CA-009** (snapshot de parámetros — inmutabilidad histórica)
```
DADO un payroll_run completado con smmlv=1.750.905
CUANDO el Super Admin actualiza smmlv a 1.900.000 (año siguiente)
  Y se consulta el payroll_run anterior
ENTONCES el payroll_run anterior sigue mostrando smmlv=1.750.905 en su parameters_snapshot
  Y el nuevo valor 1.900.000 aplica solo a períodos con start_date >= nueva vigencia
```

**CA-010** (novedades)
```
DADO un período en draft con un empleado
CUANDO se agrega novedad 'bonus' con amount=500.000 y applies_ibc=false
  Y se ejecuta el run
ENTONCES total_earned incluye +500.000
  Y ibc no se modifica
```

**CA-016** (período complementario — D-003)
```
DADO un payroll_period en status='closed'
CUANDO un usuario con payroll.manage crea un período complementario vinculado
ENTONCES se crea un nuevo payroll_period con is_complementary=true
  Y original_period_id apunta al período cerrado
  Y el período complementario inicia en status='draft'
  Y el período original permanece sin cambios
```

**CA-017** (inmutabilidad total del período cerrado)
```
DADO un payroll_period en status='closed'
CUANDO se intenta modificar directamente un payroll_item de ese período
ENTONCES recibe HTTP 422
  Y el payroll_item original no cambia
  Y el intento queda registrado en audit_logs
```

**CA-N — Bloqueo de cierre por parámetros faltantes**
```
DADO un payroll_period en status='draft' o 'processing'
CUANDO el usuario intenta POST /api/payroll/periods/{id}/close
ENTONCES el sistema valida que existan para el período:
  (a) parámetro 'smmlv' con valor vigente a la fecha del período
  (b) parámetro 'auxilio_transporte' con valor vigente
  (c) parámetro 'uvt' con valor vigente y verified_at no nulo
      (obligatorio si hay empleados sujetos a retención en el período)
  (d) tasa ARL vigente (global o scope='company') para cada clase de riesgo presente
  (e) company_tax_profile con review_status='validated' y valid_from <= start_date
      y (valid_until IS NULL OR valid_until >= end_date) para la empresa
SI falta cualquiera de los anteriores:
  recibe HTTP 422 con array 'missing_params' indicando exactamente qué falta
  Y el período permanece en su estado anterior sin modificar
  Y el intento queda registrado en audit_logs
```

### 7.4 RBAC

**CA-011**
```
DADO un usuario con solo permiso 'payroll.view'
CUANDO intenta POST /api/payroll/periods/{id}/run
ENTONCES recibe HTTP 403
```

**CA-012**
```
DADO un usuario con 'payroll.manage' pero sin 'payroll.close'
CUANDO intenta POST /api/payroll/periods/{id}/close
ENTONCES recibe HTTP 403
```

**CA-013** (parafiscales — registro de criterio)
```
DADO una empresa configurada como persona jurídica contribuyente
  Y un empleado con salary=1.750.905 (< 10 SMMLV)
CUANDO se ejecuta el payroll_run
ENTONCES payroll_items.parafiscal_exempt = TRUE
  Y payroll_items.parafiscal_exempt_basis contiene referencia a 'Art. 114-1 ET'
  Y sena_employer = 0, icbf_employer = 0
```

### 7.5 Matriz de pruebas

| Capa | Tipo | Prioridad | Descripción |
|---|---|---|---|
| Backend | Feature test | Crítico | CA-006 — cálculo salario mínimo 2026 |
| Backend | Feature test | Crítico | CA-008 — aislamiento multiempresa |
| Backend | Feature test | Crítico | CA-007 y CA-017 — período cerrado inmutable |
| Backend | Feature test | Crítico | CA-N — cierre bloqueado si faltan parámetros obligatorios (smmlv, auxilio, uvt, tasa ARL, tax_profile validado) |
| Backend | Feature test | Crítico | CA-009 — snapshot de parámetros histórico |
| Backend | Feature test | Alta | CA-016 — período complementario |
| Backend | Feature test | Alta | RBAC payroll.view vs payroll.manage vs payroll.close |
| Backend | Feature test | Alta | CA-004 — versioning de parámetros |
| Backend | Feature test | Alta | CA-013 — registro criterio parafiscales |
| Backend | Unit test | Alta | `PayrollCalculatorService` — fórmulas por separado con los casos de prueba de §6 |
| Backend | Unit test | Alta | `PayrollParameterService::getValueAt()` — fechas en rango, fuera de rango, scope company vs global |
| Backend | Unit test | Alta | Retención en la fuente — Procedimiento 1, pasos de depuración |
| Backend | Feature test | Media | Empleado sin contrato activo no genera payroll_item |
| Backend | Feature test | Media | CA-003 — company_id incorrecto retorna 404 |
| E2E (Fase posterior) | Playwright | Media | Flujo completo: crear período → ejecutar → aprobar → ver recibo |

---

## 8. Riesgos, brechas y decisiones pendientes

### 8.1 Riesgos

| # | Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|---|
| R-001 | Tasas o fórmulas legales desactualizadas o incorrectas | Media | Alto | Parámetros configurables; verificación obligatoria por contador antes de producción |
| R-002 | Cálculo incorrecto del IBC (salarial vs no salarial) | Alta | Alto | Tests unitarios exhaustivos con los casos de §6; revisión por abogado laboral |
| R-003 | Modificación accidental de nóminas cerradas | Baja | Alto | Guard de estado en toda mutación; auditoría en audit_logs; inmutabilidad D-003 |
| R-004 | Pérdida de historial de parámetros al actualizar | Baja | Alto | Solo se crean registros nuevos, nunca se actualizan los existentes |
| R-005 | Diferencia entre días laborables y días calendario | Media | Medio | El MVP usa días naturales / 360; documentar la convención en la UI |
| R-006 | Empleados sin contrato activo al ejecutar el run | Media | Medio | El run omite y registra en `error_log` los empleados sin contrato activo; avisa al usuario |
| R-007 | Concurrencia: dos admins ejecutan el run simultáneamente | Baja | Medio | Lock optimista en `payroll_runs.status` |
| R-008 | ~~Valor UVT 2026 no cargado~~ **RESUELTO** | — | — | UVT 2026 = $52.374 (Resolución DIAN 000238 de 2025). Cargar en seed con `verified_at = '2026-10-20'`. |
| R-009 | Clasificación incorrecta del tipo de empleador para exención parafiscal | Media | Alto | Ver §10 — requiere validación por contador o abogado antes de activar |

### 8.2 Brechas de datos que bloquean funcionalidades

| Brecha | Tabla afectada | Bloquea | Acción requerida |
|---|---|---|---|
| `attendances` sin horas extra | `attendances` | Horas extra en nómina | Decisión KI-001 previa a Fase D |
| `permission_requests` sin campo `is_paid` | `permission_requests` | Descuento de permisos no remunerados | Agregar columna `is_paid BOOLEAN DEFAULT TRUE` en migración de Fase A |
| `sick_leaves.type` sin distinción ARL/EPS | `sick_leaves` | Cálculo correcto de incapacidades | Agregar enum `origin ENUM('common','labor')` en migración de Fase A |
| `employees` sin `salary_type` | `employees` | Empleados con salario diario u horario | Cubierto en `employee_contracts.salary_type` — no modificar employees |
| Parámetro UVT 2026 no verificado | `payroll_parameters` | Cálculo de retención en la fuente | Cargar el valor verificado antes de activar retención |

---

## 9. Decisiones arquitectónicas incorporadas

Las siguientes decisiones fueron aprobadas por Fidel y están incorporadas en este SPEC. No son preguntas abiertas.

### D-001 — Tabla `employee_contracts` independiente

**DECIDIDA: SÍ se crea.**

`employees` conserva `salary` y `contract_type` como campos de lectura rápida (desnormalizados), sincronizados al crear o modificar un contrato. La fuente de verdad para nómina es `employee_contracts`. El historial completo de contratos y salarios queda en esta tabla.

### D-002 — Períodos de nómina independientes de períodos contables

**DECIDIDA: independientes.**

`payroll_periods` no tiene FK ni dependencia de `accounting_periods`. La nómina funciona sin plan de cuentas configurado. La integración contable (asiento automático) va a la Fase B. No hay bloqueo cruzado entre el cierre de nómina y el estado del período contable.

### D-003 — Nóminas cerradas son inmutables

**DECIDIDA: inmutabilidad total.**

- Correcciones ordinarias → nómina complementaria (`is_complementary=TRUE`) vinculada al período original.
- Anulación completa → reversión controlada: usuario autorizado, `reversal_reason` obligatorio, registro en `audit_logs`, el período queda en `cancelled`.
- Nunca se elimina historial (`payroll_items`, `payroll_item_lines`).
- El modelo de datos incorpora los campos necesarios en `payroll_periods` (ver §4.4).

### D-004 — Exención parafiscales no automática

**DECIDIDA: evaluación según condiciones reales de la Ley 1607/2012 (Art. 114-1 ET).**

El sistema evalúa: tipo de empleador (PJ contribuyente vs no), nivel salarial por trabajador (< 10 SMMLV). Registra el criterio aplicado en `payroll_items.parafiscal_exempt_basis`. Permite revisión por usuario autorizado. No simplifica a un criterio de número de empleados. Ver §6.7 para el detalle del cálculo y §10 para la validación profesional requerida.

### D-005 — Asiento contable automático en Fase B

**DECIDIDA: asiento contable automático NO está en el MVP.**

La Fase A no tiene dependencia de `chart_of_accounts` ni de `accounting_account_configs`. La nómina se puede cerrar y el recibo se puede generar sin que exista un plan de cuentas. La integración contable se implementa en la Fase B, que requiere que el módulo de contabilidad esté configurado.

---

## 10. Decisiones pendientes de validación profesional

Las siguientes cuestiones no pueden ser decididas técnicamente. Requieren el criterio de un **contador público** o **abogado laboral** colombiano antes de usar el módulo en producción. El sistema está diseñado para soportar cualquiera de las respuestas, pero la configuración inicial debe hacerse con criterio profesional.

### VP-001 — Valor de la UVT para 2026 ✓ RESUELTO

**Estado:** RESUELTO. UVT 2026 = **$52.374** (Resolución DIAN 000238 de 2025, verificada 2026-10-20).
**Acción para Juan:** cargar en seed con `key = 'uvt'`, `value = '52374'`, `currency = 'COP'`, `valid_from = '2026-01-01'`, `source_norm = 'Resolución DIAN 000238 de 2025'`, `verified_at = '2026-10-20'`.
**Bloqueo residual:** el cálculo de retención permanece desactivado si `verified_at` es nulo (salvaguarda para futuras actualizaciones anuales).

### VP-002 — Naturaleza jurídica del empleador para exención parafiscal

**Requerido para:** determinar si la empresa cliente aplica a la exoneración del Art. 114-1 ET (SENA, ICBF y aporte patronal a salud).
**Acción:** el contador de la empresa cliente debe confirmar la naturaleza jurídica (`legal_nature`) y el tipo de contribuyente (`tax_contributor_type`). Si aplica exoneración, confirmar los criterios (`exempt_basis`) y cambiar `review_status` a `validated`.
**En el sistema:** registrar en `company_tax_profiles` (tabla §4.5b). Mientras `review_status != 'validated'`, el sistema no activa la exoneración aunque `parafiscal_exempt = true`. El contador firma como `verified_by` con `verified_at`.
**Bloqueo:** una nómina no puede cerrarse si la empresa no tiene un `company_tax_profile` con `review_status = 'validated'` vigente para el período (CA-N).

### VP-003 — Tasas reales de ARL por empresa

**Requerido para:** calcular correctamente el aporte patronal a ARL.
**Acción:** cada empresa cliente debe suministrar la tasa real asignada por su ARL (no solo la clase de riesgo del cargo). Crear un parámetro de scope='company' con la tasa real.

### VP-004 — Escala completa del fondo de solidaridad pensional

**Requerido para:** calcular el aporte adicional al fondo de solidaridad para empleados con IBC > 4 SMMLV.
**Acción:** el contador debe confirmar la escala completa vigente en 2026 (los tramos descritos en §6.4 son una referencia que debe ser verificada con el texto actualizado del Art. 27 Ley 100/1993).

### VP-005 — Tabla del Art. 383 ET actualizada para 2026

**Requerido para:** aplicar correctamente la tabla de retención en la fuente (§6.15, Paso 8).
**Acción:** el contador debe confirmar los rangos en UVT y los porcentajes de la tabla Art. 383 ET según la última reforma tributaria vigente para 2026. La tabla en §6.15 es una referencia histórica que puede haber cambiado.

### VP-006 — Condiciones para aplicar el procedimiento de retención a salarios integrales

**Requerido para:** la Fase A puede incluir empleados con salario integral. Las reglas de retención para salario integral difieren.
**Acción:** el contador debe confirmar si el MVP debe calcular retención para contratos `salary_modality = 'integral'` y cuál es el procedimiento correcto.

---

---

## 11. Validaciones profesionales pendientes y controles de bloqueo

Esta sección consolida el estado de todas las validaciones que requieren criterio profesional externo, con el efecto exacto que tiene cada una sobre el sistema mientras no está validada.

| ID | Concepto | Estado | Efecto si no está validado |
|---|---|---|---|
| VP-001 | UVT 2026 ($52.374, Resolución DIAN 000238 de 2025) | **RESUELTO** | — |
| VP-002 | Perfil tributario por empresa (naturaleza jurídica, elegibilidad exoneración Art. 114-1 ET) | Pendiente por empresa | Bloquea cierre de nómina (CA-N). Exoneración parafiscal desactivada aunque `parafiscal_exempt = true`. |
| VP-003 | Tasa ARL real por empresa (asignada por la ARL según CIIU) | Pendiente por empresa | Usa tasa mínima del rango de clase (`arl_clase_N_pct`). La liquidación muestra advertencia visible indicando que se usó la tasa de referencia, no la tasa real. |
| VP-004 | Escala Fondo Solidaridad Pensional (Art. 27 Ley 100 — IBC > 4 SMMLV) | Pendiente verificación | Deshabilita el cálculo del FSP para todos los empleados con IBC > 4 SMMLV. Muestra advertencia en el comprobante de nómina y en el detalle del run. `solidarity_fund` queda en $0. |
| VP-005 | Tabla Art. 383 ET actualizada 2026 (rangos en UVT y porcentajes de retención) | Pendiente verificación | Usa la tabla ingresada en §6.15. Bloquea el cálculo de retención si el parámetro `uvt` no tiene `verified_at` no nulo. El comprobante muestra advertencia de "tabla Art. 383 ET pendiente de verificación profesional". |
| VP-006 | Procedimiento de retención para salario integral (`salary_modality = 'integral'`) | Pendiente | Deshabilita el cálculo de retención para contratos integrales. `withholding_tax = 0` para esos empleados. El comprobante muestra advertencia visible: "retención en la fuente no calculada para salario integral — requiere validación profesional". |

### Comportamiento del sistema por VP pendiente

#### VP-002 — Perfil tributario no validado
- **Pantalla afectada:** modal de cierre de período (`POST /api/payroll/periods/{id}/close`).
- **Comportamiento:** HTTP 422 con `{ "error": "missing_params", "details": ["company_tax_profile_not_validated"] }`.
- **Resolución:** el administrador crea o actualiza un `company_tax_profile` con `review_status = 'validated'`. Solo un usuario con `payroll.manage` puede hacerlo; el cambio queda en `audit_logs`.

#### VP-003 — Tasa ARL de referencia usada
- **Pantalla afectada:** comprobante de nómina del empleado, vista de `payroll_items`.
- **Comportamiento:** la nómina se procesa con la tasa mínima del rango. En el comprobante aparece el badge de advertencia: "ARL: tasa de referencia clase N — verificar con su ARL".
- **Resolución:** crear parámetro `scope = 'company'` con la tasa real asignada por la ARL.

#### VP-004 — Fondo de solidaridad pensional deshabilitado
- **Pantalla afectada:** detalle del `payroll_run`, comprobante de empleados con IBC > 4 SMMLV.
- **Comportamiento:** `solidarity_fund = 0` para todos los empleados afectados. Advertencia en el run: "Fondo de solidaridad pensional no calculado para N empleados — escala pendiente de verificación".
- **Resolución:** contador confirma la escala y se carga como parámetro verificado. El sistema activa el cálculo a partir del siguiente período.

#### VP-005 — Tabla Art. 383 ET pendiente de verificación
- **Pantalla afectada:** comprobante de retención, vista de `payroll_items.withholding_tax`.
- **Comportamiento:** el cálculo se realiza con la tabla de §6.15 pero el comprobante muestra: "Retención calculada sobre tabla de referencia — requiere verificación por contador antes de producción". Si `uvt` no tiene `verified_at`, el cálculo se bloquea por completo y `withholding_tax = 0`.
- **Resolución:** contador confirma los rangos; se actualiza la tabla como parámetros verificados.

#### VP-006 — Retención para salario integral no calculada
- **Pantalla afectada:** comprobante de empleados con `salary_modality = 'integral'`.
- **Comportamiento:** `withholding_tax = 0` para contratos integrales. El comprobante muestra badge rojo: "Retención no calculada — contrato integral requiere procedimiento especial (Art. 384 ET)".
- **Resolución:** contador define el procedimiento aplicable; se implementa en la siguiente fase tras aprobación del SPEC correspondiente.

---

*Fin de SPEC-018 — versión 3, ajustes Fidel incorporados, pendiente revisión final de PEPE.*
