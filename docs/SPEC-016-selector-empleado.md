# SPEC-016 — Selector de empleado con búsqueda server-side

**Estado:** LISTO PARA IMPLEMENTAR  
**Fecha:** 2026-10-13  
**Hallazgo origen:** C1 — `docs/PERFORMANCE_DIAGNOSIS.md`  
**Producido por:** SPEC-PLANNER

---

## Objetivo

Reemplazar la carga masiva de todos los empleados activos en el selector de empleado por un componente con búsqueda progresiva: el backend filtra y limita; el frontend busca con debounce. Con 100k empleados activos, el tiempo de carga inicial debe ser imperceptible y el TTFB de una búsqueda debe quedar por debajo de 200 ms.

---

## 1. Decisión de diseño — Opción B elegida

**Nuevo tipo de campo `"relation-search"` con su propio componente `RelationSearchField`.**

No se modifica `relation-select` ni `RelationSelectField`.

**Justificación:**
- Retrocompatibilidad garantizada: los 15+ usos de `relation-select` con catálogos pequeños no se tocan.
- Claridad semántica: `relation-select` = carga completa en mount; `relation-search` = búsqueda progresiva.
- Menor superficie de regresión: imposible activar el nuevo comportamiento accidentalmente en selectores existentes.

---

## 2. Archivos afectados

| Archivo | Acción |
|---------|--------|
| `backend/app/Http/Controllers/Api/EmployeeController.php` | Modificar `selector()` |
| `frontend/src/components/crud/crud-modal.tsx` | Agregar tipo `"relation-search"` y componente `RelationSearchField` |
| `frontend/src/app/app/asistencia/page.tsx:19` | `type: "relation-select"` → `type: "relation-search"` |
| `frontend/src/app/app/documentos/page.tsx:18` | `type: "relation-select"` → `type: "relation-search"` |
| `frontend/src/app/app/incapacidades/page.tsx:19` | `type: "relation-select"` → `type: "relation-search"` |
| `frontend/src/app/app/permisos/page.tsx:18` | `type: "relation-select"` → `type: "relation-search"` |
| `frontend/src/app/app/vacaciones/page.tsx:18` | `type: "relation-select"` → `type: "relation-search"` |
| `frontend/src/app/app/usuarios/page.tsx:152` | `type: "relation-select"` → `type: "relation-search"` |
| `backend/tests/Feature/EmployeeSelectorTest.php` | Crear (nuevo) |

---

## 3. Backend — `EmployeeController::selector()` modificado

### Ruta

`GET /api/employees/selector` — misma ruta existente. **No se modifica `routes/api.php`.**

Sin middleware `can:`. Disponible para cualquier usuario autenticado de la empresa (conforme a M31 del CORE_CONTRACT).

### Parámetros GET (todos opcionales)

| Parámetro | Tipo | Default | Máx | Descripción |
|-----------|------|---------|-----|-------------|
| `search` | string | — | — | Filtro de texto. Mínimo 2 caracteres para activar. Con < 2 chars: ignorado. |
| `limit` | integer | 30 | 50 | Cantidad de resultados. Valores > 50 se truncan a 50. |
| `id` | integer | — | — | Fetch individual por ID. Ignora `search` y `limit`. |

### Comportamientos

**Sin `?search` (o search < 2 chars):**
```
WHERE company_id = ? AND employment_status = 'active'
ORDER BY first_name ASC
LIMIT 30
```

**Con `?search` (≥ 2 chars):**
```
WHERE company_id = ? AND employment_status = 'active'
  AND (first_name LIKE '%search%' OR last_name LIKE '%search%')
ORDER BY first_name ASC
LIMIT {limit}  -- máx 50
```

**Con `?id=X` (modo edición):**
```
WHERE company_id = ? AND id = X
-- SIN filtrar employment_status (registros históricos pueden tener empleados inactivos)
LIMIT 1
```

### Respuesta JSON

Siempre un array (nunca objeto paginado):
```json
[{"id": 1, "label": "Juan García"}, ...]
```
Array vacío `[]` si no hay resultados.

### Índice existente

`employees_company_status_index (company_id, employment_status)` — ya existe. Suficiente para queries sin `search`.

**Nota conocida (ponytail):** LIKE bilateral (`%search%`) no usa índice en el campo nombre. Aceptable para < 10k empleados activos por empresa. Si escala: agregar índice `(company_id, employment_status, first_name)` o migrar a búsqueda por prefijo (`search%`).

---

## 4. Frontend — `RelationSearchField`

### Tipo en `CrudField`

```typescript
type?: "text" | "email" | "password" | "date" | "time" | "datetime-local"
     | "number" | "select" | "textarea" | "relation-select" | "relation-search" | "file";
```

No se agregan propiedades nuevas a `CrudField`. `endpoint` se reutiliza igual.

### `normalizeValue` (línea 73 de crud-modal.tsx)

```typescript
if (field.type === "number" || field.type === "relation-select"
    || field.type === "relation-search") return Number(value);
```

### Estado del componente

```typescript
const [query, setQuery]                 = useState("");
const [results, setResults]             = useState<RelationOption[]>([]);
const [selectedId, setSelectedId]       = useState<number | "">("");
const [selectedLabel, setSelectedLabel] = useState("");
const [loading, setLoading]             = useState(false);
const [open, setOpen]                   = useState(false);
```

### UX por estado

**Modo crear (`defaultVal = ""`):**
- Input vacío, placeholder `"Buscar empleado…"`
- Al hacer focus: carga los 30 primeros (`GET {endpoint}?limit=30`)
- Con texto ≥ 2 chars: `GET {endpoint}?search=X&limit=30` (debounce 300ms)
- Con texto < 2 chars: muestra los 30 primeros sin filtrar

**Modo editar (`defaultVal = "42"`):**
- Al montar: `GET {endpoint}?id=42` para resolver el label
- Mientras resuelve: placeholder neutro
- Tras resolver: input muestra el label, `selectedId = 42`
- Si el empleado no existe en la empresa: input vacío

**Mientras escribe:**
- Debounce 300ms (consistencia con `useApiTable`)
- Spinner inline (`loading = true`)
- AbortController cancela el request anterior

**Con resultados:** dropdown con opciones clickeables  
**Sin resultados:** dropdown muestra `"Sin resultados"`  
**Error de carga:** dropdown muestra `"Error al cargar"`, campo hidden conserva valor previo

**Cerrar dropdown:**
- Click fuera (onBlur con delay 150ms para permitir click en opción)
- Escape: cierra dropdown, limpia query si no hay selección

### Campo hidden

```tsx
<input type="hidden" name={field.name} value={selectedId} />
```

### Validación en `submit()`

Agregar para campos `relation-search required`:
```typescript
if (field.type === "relation-search" && field.required && !selectedId) {
    // mostrar error de validación en el campo
}
```
Razón: `<input type="hidden">` no participa en constraint validation nativa del browser.

### Caso especial: campo opcional (`usuarios/page.tsx`, `required: false`)

Si el usuario no selecciona empleado, `selectedId = ""` y `normalizeValue("") = Number("") = 0`. JUAN debe verificar si el campo tiene `omitWhenEmpty: true` o agregar lógica en `normalizeValue` para retornar `null` cuando value es `""` y el campo no es required. Revisar comportamiento actual antes de implementar.

### Accesibilidad mínima

- Input: `role="combobox"`, `aria-autocomplete="list"`, `aria-expanded={open}`, `aria-haspopup="listbox"`
- Dropdown: `role="listbox"`; opciones: `role="option"`
- **Tab:** mueve foco al siguiente campo (cierra dropdown)
- **Escape:** cierra dropdown; limpia query si no hay selección confirmada
- **Enter:** selecciona opción resaltada si dropdown está abierto
- **↑↓:** navegan opciones del dropdown

### Librería de UI

Sin dependencias nuevas. Tailwind + tokens CSS existentes (`border-input`, `bg-background`, `text-foreground`).

---

## 5. Cambios en las 6 páginas

Solo se cambia `type: "relation-select"` → `type: "relation-search"` en cada campo `employee_id`. Endpoint, name, label y required no cambian.

| Archivo | Línea | Cambio |
|---------|-------|--------|
| `asistencia/page.tsx` | 19 | `type: "relation-search"` |
| `documentos/page.tsx` | 18 | `type: "relation-search"` |
| `incapacidades/page.tsx` | 19 | `type: "relation-search"` |
| `permisos/page.tsx` | 18 | `type: "relation-search"` |
| `vacaciones/page.tsx` | 18 | `type: "relation-search"` |
| `usuarios/page.tsx` | 152 | `type: "relation-search"` (required: false — ver riesgo 2) |

---

## 6. Criterios de aceptación

- [ ] CA-1. `GET /employees/selector` sin parámetros devuelve máx 30 registros de la empresa correcta.
- [ ] CA-2. `GET /employees/selector?search=garcia` devuelve solo empleados con "garcia" en nombre o apellido. Máx 30.
- [ ] CA-3. `GET /employees/selector?id=5` devuelve `[{id:5, label:"..."}]` si pertenece a la empresa; `[]` si no.
- [ ] CA-4. `GET /employees/selector?limit=100` respeta el máximo de 50.
- [ ] CA-5. La respuesta siempre es un array (no objeto paginado).
- [ ] CA-6. `company_id` scope: token de empresa A no puede ver empleados de empresa B.
- [ ] CA-7. `?id=X` devuelve el empleado aunque `employment_status` sea inactivo.
- [ ] CA-8. `?search=a` (1 char) devuelve los 30 primeros sin filtrar.
- [ ] CA-9. Modal en modo crear: input vacío, carga 30 primeros al hacer focus.
- [ ] CA-10. Al escribir ≥ 2 chars, una sola petición al backend (debounce 300ms).
- [ ] CA-11. Modal en modo editar: muestra el nombre del empleado previamente seleccionado.
- [ ] CA-12. Al seleccionar un empleado, el campo hidden recibe el id correcto y el formulario lo envía como `employee_id` numérico.
- [ ] CA-13. Campo `relation-search required` vacío: error visible al usuario antes de llamar al API.
- [ ] CA-14. Escape cierra dropdown. Flechas ↑↓ navegan. Enter selecciona.
- [ ] CA-15. Los 15+ campos `relation-select` existentes siguen funcionando sin cambios (regresión: departments, positions).
- [ ] CA-16. Build Next.js pasa sin errores de TypeScript (`next build`).
- [ ] CA-17. Con 100k empleados activos en BD, `GET /employees/selector` responde en < 200 ms.

---

## 7. Estrategia de pruebas

### Backend — `EmployeeSelectorTest.php` (nuevo)

```
- sin parámetros → ≤ 30 resultados, todos de la empresa correcta
- ?limit=5 → devuelve 5
- ?limit=100 → devuelve máx 50
- ?search=... → filtra por nombre/apellido
- ?search=a (1 char) → devuelve 30 primeros sin filtrar
- ?id=X válido → devuelve 1 resultado
- ?id=X de otra empresa → devuelve []
- ?id=X de empleado inactivo → devuelve el empleado
- sin auth → 401
```

### Frontend

`npm run build` sin errores de TypeScript (cubre CA-16). Sin tests de componente (sin framework configurado).

---

## 8. Riesgos conocidos

1. **LIKE bilateral** no usa índice en nombre. Aceptable para < 10k activos. Monitorear en producción.
2. **`selectedId` vacío en campo opcional:** `normalizeValue("") = 0`. Verificar si se debe retornar `null`. Resolver antes de implementar.
3. **Validación `required` manual en `submit()`:** si se omite, el backend responde 422 (funcional pero UX inferior).
4. **`defaultVal = "0"` o `""` en modo edición:** no debe mostrar error visual si el registro no tenía empleado asignado.

---

## 9. Fuera de alcance

- Otros selectores (`clients`, `deals`, `departments`, `positions`, `categories`, `brands`, `units`)
- Migrar todos los `relation-select` a server-side
- Rediseño del CrudModal
- Índice FULLTEXT
- Paginación infinita o virtualización del dropdown

---

## Criterio de DONE

VERIFIED cuando CA-1 a CA-17 marcados [x], `EmployeeSelectorTest` pasa en verde, build Next.js sin errores de TypeScript, NANCY aprueba, y MARGARITA verifica que los 6 módulos abren modal / buscan / seleccionan / guardan correctamente y que departments y positions siguen funcionando.
