# Matriz de validaciones profesionales — SPEC-018 Nómina

> Documento para: contador público colombiano
> Preparado por: PEPE / FidelOS — 2026-10-22
> Referencia: SPEC-018-nomina-liquidaciones.md §10 y §11

Este documento reúne las 5 preguntas que el sistema no puede responder técnicamente.
Para cada una se incluyen: la pregunta exacta, la fuente legal, los casos de prueba con valores esperados, y la acción concreta que debe tomar el contador.

---

## VP-002 — Naturaleza jurídica del empleador: exoneración Art. 114-1 ET

### Pregunta para el contador

> ¿La empresa cliente está exonerada de los aportes patronales a SENA, ICBF y salud sobre el componente salarial (Art. 114-1 ET, Ley 1607/2012)?

### Fuente legal

Art. 114-1 ET (modificado por Ley 2277/2022). Aplica a sociedades y personas jurídicas contribuyentes del impuesto de renta ordinario con empleados que devenguen menos de 10 SMMLV.

No aplica a: personas naturales empleadoras, entidades sin ánimo de lucro, entidades del sector público, cooperativas de trabajo asociado.

### Datos que debe suministrar por empresa

| Campo | Tipo | Ejemplo |
|-------|------|---------|
| `entity_type` | enum: `sociedad`, `persona_natural`, `esal`, `publico`, `cooperativa` | `sociedad` |
| `parafiscal_exempt` | boolean | `true` |
| `health_exempt` | boolean | `true` |
| `review_status` | `validated` | `validated` |
| `valid_from` | fecha | `2026-01-01` |

### Efecto en nómina

Si `parafiscal_exempt = true` y `health_exempt = true`: SENA (2%) e ICBF (3%) no se calculan en el aporte patronal. Salud patronal (8.5%) se calcula sobre el SMMLV para empleados < 10 SMMLV.

Si el perfil no existe o `review_status != 'validated'`: cierre de nómina devuelve HTTP 422 (bloqueo CA-N).

### Acción del contador

1. Para cada empresa cliente, clasificar en uno de los `entity_type` válidos.
2. Confirmar si aplica la exoneración del Art. 114-1 ET.
3. Registrar en el sistema creando `company_tax_profile` con los campos de la tabla anterior.

---

## VP-003 — Tasa ARL real por empresa

### Pregunta para el contador

> ¿Cuál es la tasa ARL exacta asignada por la ARL a esta empresa?

### Fuente legal

Decreto 1607/2002 — tabla de clases de riesgo y tasas de cotización.

### Tabla de referencia (tasas mínimas por clase)

| Clase | Actividad típica | Tasa mínima | Tasa máxima |
|-------|------------------|-------------|-------------|
| I | Financiero, administrativo | 0.348% | 0.522% |
| II | Agrícola, manufactura ligera | 0.435% | 1.044% |
| III | Manufactura media, construcción liviana | 0.783% | 2.436% |
| IV | Industria pesada, minería liviana | 1.740% | 4.350% |
| V | Minería, explosivos, alto riesgo | 3.219% | 6.960% |

El sistema usa la tasa mínima si no hay tasa real registrada. El comprobante muestra advertencia.

### Casos de prueba para verificar

| Salario IBC | Tasa real (ej. clase II real) | Aporte patronal ARL esperado |
|-------------|-------------------------------|------------------------------|
| $2.000.000 | 0.52% | $10.400 |
| $5.000.000 | 1.74% | $87.000 |
| $10.000.000 | 3.219% | $321.900 |

### Acción del contador

1. Obtener el certificado de clase de riesgo de la ARL para cada empresa cliente.
2. Registrar la tasa real en el sistema como parámetro `scope = 'company'`.

---

## VP-004 — Escala Fondo de Solidaridad Pensional

### Pregunta para el contador

> ¿Es correcta la escala de aportes adicionales al Fondo de Solidaridad Pensional vigente en 2026?

### Fuente legal

Art. 27 Ley 100/1993 (modificado por Ley 797/2003). SMMLV 2026 = $1.423.500. Umbral de entrada FSP = 4 × $1.423.500 = **$5.694.000**.

### Escala de referencia en el sistema

| IBC del empleado | Aporte adicional al FSP |
|------------------|-------------------------|
| > 4 SMMLV y ≤ 16 SMMLV | 1% del IBC |
| > 16 SMMLV y ≤ 17 SMMLV | 1.2% del IBC |
| > 17 SMMLV y ≤ 18 SMMLV | 1.4% del IBC |
| > 18 SMMLV y ≤ 19 SMMLV | 1.6% del IBC |
| > 19 SMMLV y ≤ 20 SMMLV | 1.8% del IBC |
| > 20 SMMLV | 2% del IBC |

### Casos de prueba para verificar

| Salario (IBC) | ¿Aplica FSP? | Tasa | Aporte FSP esperado |
|---------------|--------------|------|---------------------|
| $4.000.000 | No (< 4 SMMLV) | — | $0 |
| $6.000.000 | Sí | 1% | $60.000 |
| $24.000.000 | Sí | 2% | $480.000 |

### Acción del contador

1. Confirmar que la escala del Art. 27 Ley 100 es la vigente en 2026.
2. Si hay cambio por reforma posterior, suministrar la escala actualizada con tramos exactos.
3. Registrar como parámetros verificados con `valid_from = '2026-01-01'`.

---

## VP-005 — Tabla Art. 383 ET — Retención en la fuente sobre salarios

### Pregunta para el contador

> ¿Es correcta la tabla de rangos en UVT y porcentajes del Art. 383 ET para retención en la fuente sobre salarios, vigente para 2026?

### Fuente legal

Art. 383 ET (modificado por Ley 2277/2022). UVT 2026 = **$52.374** (Resolución DIAN 000238 de 2025).

### Tabla de referencia en el sistema

| Desde (UVT/mes) | Hasta (UVT/mes) | Retención marginal |
|-----------------|-----------------|--------------------|
| 0 | 95 | 0% |
| 95 | 150 | 19% |
| 150 | 360 | 28% |
| 360 | 640 | 33% |
| 640 | 945 | 35% |
| 945 | 2300 | 37% |
| > 2300 | — | 39% |

### Casos de prueba con UVT 2026 = $52.374

| Salario bruto (COP) | IBC en UVT | Rango | Retención esperada (COP) |
|--------------------|------------|-------|--------------------------|
| $3.000.000 | 57.3 UVT | 0% | $0 |
| $5.500.000 | 104.9 UVT | 19% | (104.9 − 95) × 19% × $52.374 ≈ **$98.462** |
| $10.000.000 | 190.9 UVT | 28% | (190.9 − 150) × 28% × $52.374 + 10.45 × $52.374 ≈ **$1.149.685** |

> El sistema bloquea el cálculo de retención si `uvt.verified_at` es nulo. Sin verificación: `withholding_tax = 0`.

### Acción del contador

1. Confirmar que la tabla refleja el Art. 383 ET vigente para 2026.
2. Verificar porcentajes marginales y valores de "retención mínima" por tramo.
3. Si hay cambio, suministrar la tabla corregida.
4. Aprobar actualizando `uvt.verified_at` para UVT 2026 = $52.374.

---

## VP-006 — Retención en la fuente para salario integral

### Pregunta para el contador

> ¿Cómo debe aplicarse la retención en la fuente para empleados con salario integral en 2026?

### Fuente legal

Art. 384 ET — Retención mínima sobre pagos laborales. Art. 18 CST — Salario integral (min. 13 SMMLV = $18.505.500 en 2026). El procedimiento difiere del Art. 383 ET.

### Preguntas específicas

1. ¿La base gravable para retención del salario integral es el 70% del total, o el 100%?
2. ¿Se aplica la tabla del Art. 383 ET o la del Art. 384 ET?
3. ¿Hay cambio de criterio DIAN para 2026 respecto de 2025?

### Casos de prueba (completar por el contador)

| Salario integral | Factor salarial (70%) | Base gravable retención | Tabla aplicable | Retención esperada |
|------------------|-----------------------|-------------------------|-----------------|--------------------|
| $20.000.000 | $14.000.000 | **¿?** | **¿Art. 383 o 384?** | **¿?** |
| $30.000.000 | $21.000.000 | **¿?** | **¿Art. 383 o 384?** | **¿?** |

> Mientras VP-006 no está resuelto: `withholding_tax = 0` para contratos integrales con badge rojo en el comprobante.

### Acción del contador

1. Confirmar la base gravable correcta y la tabla aplicable.
2. Suministrar al menos 2 casos de prueba con valores exactos (completar la tabla anterior).
3. Registrar la decisión para que el sistema active el cálculo de retención para contratos integrales.

---

## Resumen ejecutivo para el contador

| VP | Bloqueo actual | Urgencia |
|----|---------------|----------|
| VP-002 | Cierre de nómina bloqueado por empresa | Alta — sin esto no se puede cerrar ningún período |
| VP-003 | Tasa ARL de referencia (subestima para clase II+) | Media — funciona, pero impreciso |
| VP-004 | FSP no calculado para salarios > 4 SMMLV | Alta — subestima carga parafiscal empleados altos salarios |
| VP-005 | Retención no calculada si UVT sin verificar | Alta — todos los empleados quedan con retención $0 |
| VP-006 | Retención no calculada para salario integral | Media — afecta solo contratos integrales |
