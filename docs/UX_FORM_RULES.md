# UX_FORM_RULES.md — Reglas de formularios para TODAS las verticales

> **CORE — heredable.** Aplica a RRHH, CRM, Inventario, Compras, Ventas, Finanzas,
> Contabilidad, y toda vertical futura (Veterinaria, Inmobiliaria, Nómina, Car Wash, etc.).
>
> Origen: auditoría K-FORMS (2026-10-06). 24 issues encontrados al no tener estas
> reglas escritas en el CORE. El objetivo es que una vertical nueva no los redescubra.

---

## Regla UX-001 — Relaciones FK: nunca IDs técnicos al usuario

**Regla:**
Cualquier campo que referencie otra entidad (FK) DEBE presentarse como un selector
cargado desde la API, no como un `<input type="number">` que pida el ID interno.

**Síntoma incorrecto:**
```tsx
{ name: "employee_id", label: "ID empleado", type: "number", hint: "ID de un empleado existente" }
```

**Correcto:**
```tsx
// Custom Dialog con selector cargado desde la API:
// Carga desde GET /employees → muestra nombre completo → guarda el id internamente
```

**Por qué:** Un usuario final no debe conocer ni copiar IDs de la base de datos.
Los IDs son artefactos técnicos, no datos de negocio.

**Aplica a todos los campos FK:**
`employee_id`, `department_id`, `position_id`, `client_id`, `supplier_id`,
`product_id`, `category_id`, `brand_id`, `unit_id`, `warehouse_id`,
`deal_id`, `payable_id`, `cash_account_id`, `parent_id`, `quote_id`,
`sale_order_id`, `purchase_order_id`, y cualquier `*_id` que una vertical nueva introduzca.

---

## Regla UX-002 — Archivos: selector de archivos, nunca ruta técnica

**Regla:**
Los campos que almacenan un archivo DEBEN usar un control de carga (`<input type="file">`).
Nunca solicitar al usuario que escriba una ruta de servidor.

**Síntoma incorrecto:**
```tsx
{ name: "file_path", label: "Ruta del archivo", type: "text" }
// → El usuario tendría que escribir: /storage/documents/cv_2024.pdf
```

**Correcto:**
- Frontend: `<input type="file">` con previsualización si aplica
- Backend: `Storage::disk('public')->put(...)` en el controller
- API: multipart/form-data o endpoint dedicado de upload

**Por qué:** Las rutas físicas del servidor son detalles de infraestructura.
El usuario carga un archivo y el sistema decide dónde guardarlo.

---

## Regla UX-003 — Estados: defaults explícitos y readonly donde corresponde

**Regla:**
Todo campo de estado (`status`, `state`) debe tener:
1. Un `defaultValue` definido en el formulario de creación.
2. Opciones fijas (select), nunca texto libre.
3. Si el estado es calculado por el sistema (ej: `draft → confirmed → fulfilled`),
   el campo NO debe ser editable en el formulario general — solo a través de acciones
   específicas (botón "Confirmar", botón "Postear", etc.).

**Síntoma incorrecto:**
```tsx
{ name: "status", type: "select", options: STATUS_OPTIONS }
// Sin defaultValue → el campo queda en blanco en creación
```

**Correcto:**
```tsx
{ name: "status", type: "select", options: STATUS_OPTIONS, defaultValue: "pending" }
```

---

## Regla UX-004 — Campos catalogados: select fijo, no texto libre

**Regla:**
Si un campo tiene un conjunto finito de valores posibles (tipo de incapacidad,
tipo de documento, tipo de movimiento, etc.), DEBE ser un select con opciones
definidas en el frontend. Nunca `type: "text"` sin restricción.

**Síntoma incorrecto:**
```tsx
{ name: "type", label: "Tipo de incapacidad", type: "text" }
// → Un usuario escribe "enfermedad", otro "Enfermedad General", otro "EG"
```

**Correcto:**
```tsx
{ name: "type", type: "select", required: true, options: [
  { value: "enfermedad_general", label: "Enfermedad general" },
  { value: "accidente_trabajo", label: "Accidente de trabajo" },
  { value: "licencia_maternidad", label: "Licencia de maternidad" },
  { value: "otro", label: "Otro" },
]}
```

**Por qué:** Los datos no normalizados hacen imposible filtrar, reportar y agregar.

---

## Regla UX-005 — Multitenancy en selectores: solo datos de la empresa activa

**Regla:**
Cualquier selector que cargue entidades relacionadas DEBE retornar solo los registros
de la `company_id` activa. El Bearer token lo resuelve en el servidor vía `ResolvesCompany`.
No se necesita pasar `company_id` como parámetro de URL.

---

## Catálogo de selectores CORE por tipo de FK

| Campo | Endpoint | Label a mostrar |
|-------|----------|----------------|
| `employee_id` | `GET /employees` | `first_name + last_name` |
| `department_id` | `GET /departments` | `name` |
| `position_id` | `GET /positions` | `name` |
| `client_id` | `GET /clients` | `company_name` o `first_name + last_name` |
| `supplier_id` | `GET /suppliers` | `name` |
| `product_id` | `GET /products` | `name` + `sku` |
| `category_id` | `GET /categories` | `name` |
| `brand_id` | `GET /brands` | `name` |
| `unit_id` | `GET /units` | `name` + `symbol` |
| `warehouse_id` | `GET /warehouses` | `name` |
| `payable_id` | `GET /accounts-payable` o `/accounts-receivable` | número + proveedor/cliente |
| `cash_account_id` | `GET /cash-accounts` | `name` |
| `parent_id` (categorías/cuentas) | mismo endpoint excluyendo self | `name` con indent si jerárquico |

---

## Patrón de implementación: Custom Dialog con carga de relaciones

El patrón de referencia es el usado en Compras y Ventas:

```tsx
// Al abrir el modal, cargar todas las relaciones en paralelo
React.useEffect(() => {
  if (!open) return;
  setLoadingData(true);
  Promise.all([
    api.get("/employees", { params: { per_page: 500 } }),
    api.get("/departments", { params: { per_page: 200 } }),
  ])
    .then(([e, d]) => { setEmployees(e.data.data); setDepartments(d.data.data); })
    .finally(() => setLoadingData(false));
}, [open]);

// Renderizar con <select> nativo
<select value={employeeId} onChange={(e) => setEmployeeId(Number(e.target.value))}
  disabled={loadingData}
  className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
>
  <option value="">{loadingData ? "Cargando..." : "Seleccionar empleado"}</option>
  {employees.map((e) => (
    <option key={e.id} value={e.id}>{e.first_name} {e.last_name}</option>
  ))}
</select>
```

**Por qué `<select>` nativo:** accesible por defecto, funciona en móvil sin config extra, sin dependencia adicional.

---

## Checklist UX de formulario (verificar antes de declarar DONE)

- [ ] Ningún campo visible muestra o pide un ID numérico interno
- [ ] Ningún campo pide una ruta de servidor para archivos
- [ ] Todos los campos FK tienen selector cargado desde la API
- [ ] Todos los selectores respetan el scope de `company_id`
- [ ] Todos los campos de estado tienen `defaultValue` explícito
- [ ] Los campos catalogados usan `select`, no `text` libre
- [ ] Los estados del sistema son read-only o se cambian con acciones específicas
- [ ] El formulario funciona en móvil (scroll, tap, sin overflow)
