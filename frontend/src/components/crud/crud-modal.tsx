"use client";

import * as React from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { VoiceInputWrapper } from "@/components/ui/voice-input-wrapper";
import { api } from "@/lib/api";
import { cn } from "@/lib/utils";

type FieldOption = {
  label: string;
  value: string | number;
};

export type CrudField = {
  name: string;
  label: string;
  type?: "text" | "email" | "password" | "url" | "date" | "time" | "datetime-local" | "number" | "select" | "textarea" | "relation-select" | "relation-search" | "file";
  required?: boolean;
  placeholder?: string;
  options?: FieldOption[];
  /** Endpoint GET que devuelve [{id, label}] o [{id, name}] — solo para type="relation-select". */
  endpoint?: string;
  /** MIME types aceptados — solo para type="file". */
  accept?: string;
  colSpan?: "full";
  /** Skip sending this field when left blank, instead of overwriting the stored value with null. */
  omitWhenEmpty?: boolean;
  /** Solo visible en modo "create" — util para campos de archivo que no se re-suben al editar. */
  createOnly?: boolean;
  /** Valor por defecto cuando se crea un registro nuevo (row === null). Util para status/estado. */
  defaultValue?: string;
  /** Validacion nativa del navegador (primer filtro; el backend es el que manda). */
  pattern?: string;
  hint?: string;
  min?: number;
  max?: number;
  step?: number;
  /** Habilita el botón de dictado por voz en este campo (text / textarea). */
  voice?: boolean;
  /** El campo no puede cambiarse al editar un registro existente (campo de titularidad/ownership). */
  editReadonly?: boolean;
  /** Cuando el modo es "create", el campo se envía oculto con su defaultValue (ej. "active")
   *  en lugar de mostrar un selector editable. En modo "edit" se muestra como select normal. */
  createHidden?: boolean;
};

type CrudRow = Record<string, unknown> & { id?: number | string };

type CrudModalProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  mode: "create" | "edit";
  title: string;
  description?: string;
  resource: string;
  fields: CrudField[];
  row?: CrudRow | null;
  onSaved: () => void;
  /**
   * Modo contingencia: si viene definido y es un alta, el registro se encola
   * localmente en vez de llamar al API. Solo aplica a mode === "create".
   */
  queueSubmit?: (payload: Record<string, unknown>) => Promise<void>;
};

function normalizeValue(value: FormDataEntryValue | null, field: CrudField) {
  if (value == null || value === "" || field.type === "file") return null;
  if (field.type === "number" || field.type === "relation-select" || field.type === "relation-search") return Number(value);
  return String(value);
}

type RelationOption = { id: number; label: string };

function RelationSelectField({
  field,
  defaultVal,
  onClearError,
  errorClass,
  disabled,
}: {
  field: CrudField;
  defaultVal: string;
  onClearError: () => void;
  errorClass?: string;
  disabled?: boolean;
}) {
  const [options, setOptions] = React.useState<RelationOption[]>([]);
  const [value, setValue] = React.useState(defaultVal);
  const [loading, setLoading] = React.useState(true);

  React.useEffect(() => {
    setValue(defaultVal);
  }, [defaultVal]);

  React.useEffect(() => {
    if (!field.endpoint) return;
    setLoading(true);
    api.get(field.endpoint).then((res) => {
      const raw: unknown[] = Array.isArray(res.data)
        ? res.data
        : Array.isArray(res.data?.data)
          ? (res.data.data as unknown[])
          : [];
      setOptions(
        raw.map((item) => {
          const r = item as Record<string, unknown>;
          const label =
            r.label != null
              ? String(r.label)
              : r.first_name != null
                ? `${r.first_name} ${r.last_name ?? ""}`.trim()
                : String(r.name ?? r.id);
          return { id: Number(r.id), label };
        }),
      );
      setLoading(false);
    }).catch(() => setLoading(false));
  }, [field.endpoint]);

  return (
    <select
      name={field.name}
      required={field.required}
      value={value}
      disabled={disabled || loading}
      onChange={(e) => {
        setValue(e.target.value);
        onClearError();
      }}
      className={cn(
        "flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none transition focus-visible:border-primary",
        (disabled || loading) && "cursor-not-allowed opacity-60",
        errorClass,
      )}
    >
      {loading ? (
        <option value="">Cargando...</option>
      ) : (
        <>
          <option value="">Seleccionar</option>
          {options.map((opt) => (
            <option key={opt.id} value={opt.id}>
              {opt.label}
            </option>
          ))}
        </>
      )}
    </select>
  );
}

function RelationSearchField({
  field,
  defaultVal,
  disabled,
  errorClass,
  onClearError,
  onDropdownOpenChange,
}: {
  field: CrudField;
  defaultVal: string | number;
  disabled?: boolean;
  errorClass?: string;
  onClearError?: () => void;
  onDropdownOpenChange?: (open: boolean) => void;
}) {
  const [query, setQuery]                 = React.useState("");
  const [results, setResults]             = React.useState<RelationOption[]>([]);
  const [selectedId, setSelectedId]       = React.useState<number | null>(
    defaultVal && Number(defaultVal) ? Number(defaultVal) : null,
  );
  const [selectedLabel, setSelectedLabel] = React.useState("");
  const [loading, setLoading]             = React.useState(false);
  const [open, setOpen]                   = React.useState(false);
  const [focusedIndex, setFocusedIndex]   = React.useState(-1);
  const abortRef                          = React.useRef<AbortController | null>(null);
  const debounceRef                       = React.useRef<ReturnType<typeof setTimeout> | null>(null);

  React.useEffect(() => { onDropdownOpenChange?.(open); }, [open, onDropdownOpenChange]);

  // Resolver label en modo edición — se re-ejecuta cuando cambia el registro abierto
  React.useEffect(() => {
    if (defaultVal && Number(defaultVal)) {
      setSelectedId(Number(defaultVal));
      setSelectedLabel("");
      api.get(`${field.endpoint}?id=${defaultVal}`)
        .then((res) => {
          const raw: RelationOption[] = Array.isArray(res.data) ? (res.data as RelationOption[])
            : Array.isArray((res.data as { data?: unknown })?.data) ? (res.data as { data: RelationOption[] }).data
            : [];
          if (raw[0]) {
            setSelectedId(raw[0].id);
            setSelectedLabel(raw[0].label);
          }
        })
        .catch(() => {});
    } else {
      setSelectedId(null);
      setSelectedLabel("");
    }
  }, [defaultVal, field.endpoint]);

  const search = (term: string) => {
    abortRef.current?.abort();
    abortRef.current = new AbortController();
    setLoading(true);
    const url =
      term.length >= 2
        ? `${field.endpoint}?search=${encodeURIComponent(term)}&limit=30`
        : `${field.endpoint}?limit=30`;
    api.get(url, { signal: abortRef.current.signal })
      .then((res) => {
        const data: RelationOption[] = Array.isArray(res.data) ? (res.data as RelationOption[])
          : Array.isArray((res.data as { data?: unknown })?.data) ? (res.data as { data: RelationOption[] }).data
          : [];
        setResults(data);
        setFocusedIndex(-1);
        setLoading(false);
        setOpen(true);
      })
      .catch((e: unknown) => {
        const err = e as { code?: string; name?: string };
        if (err.code === "ERR_CANCELED" || err.name === "CanceledError") return;
        setResults([]);
        setLoading(false);
      });
  };

  const handleInput = (e: React.ChangeEvent<HTMLInputElement>) => {
    const val = e.target.value;
    setQuery(val);
    if (!val) {
      setSelectedId(null);
      setSelectedLabel("");
    }
    if (debounceRef.current) clearTimeout(debounceRef.current);
    debounceRef.current = setTimeout(() => search(val), 300);
  };

  const select = (opt: RelationOption) => {
    setSelectedId(opt.id);
    setSelectedLabel(opt.label);
    setQuery("");
    setOpen(false);
    setFocusedIndex(-1);
    onClearError?.();
  };

  const displayValue = open ? query : selectedLabel || query;

  return (
    <div className="relative">
      <input
        type="text"
        role="combobox"
        aria-autocomplete="list"
        aria-expanded={disabled ? false : open}
        aria-haspopup="listbox"
        placeholder={selectedLabel || "Buscar empleado…"}
        value={displayValue}
        disabled={disabled}
        className={cn(
          "flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring",
          disabled && "cursor-not-allowed opacity-60",
          errorClass,
        )}
        onChange={disabled ? undefined : handleInput}
        onFocus={disabled ? undefined : () => { if (!open) search(query); }}
        onBlur={disabled ? undefined : () => setTimeout(() => { setOpen(false); setFocusedIndex(-1); }, 150)}
        onKeyDown={(e) => {
          if (e.key === "Escape") {
            if (open) { setOpen(false); setQuery(""); setFocusedIndex(-1); }
            return;
          }
          if (e.key === "ArrowDown") {
            e.preventDefault();
            setFocusedIndex(i => Math.min(i + 1, results.length - 1));
            setOpen(true);
          }
          if (e.key === "ArrowUp") {
            e.preventDefault();
            setFocusedIndex(i => Math.max(i - 1, 0));
          }
          if (e.key === "Enter" && open && focusedIndex >= 0 && results[focusedIndex]) {
            e.preventDefault();
            select(results[focusedIndex]);
          }
        }}
      />
      <input type="hidden" name={field.name} value={selectedId ?? ""} />
      {!disabled && loading && (
        <span className="absolute right-2 top-2 text-muted-foreground text-xs">…</span>
      )}
      {!disabled && open && (
        <ul
          role="listbox"
          className="absolute z-50 mt-1 max-h-48 w-full overflow-auto rounded-md border border-input bg-background shadow-md text-sm"
        >
          {results.length === 0 ? (
            <li className="px-3 py-2 text-muted-foreground">Sin resultados</li>
          ) : (
            results.map((opt, idx) => (
              <li
                key={opt.id}
                role="option"
                aria-selected={opt.id === selectedId}
                className={`cursor-pointer px-3 py-2 hover:bg-muted${idx === focusedIndex ? " bg-muted" : ""}`}
                onMouseDown={() => select(opt)}
              >
                {opt.label}
              </li>
            ))
          )}
        </ul>
      )}
    </div>
  );
}

function fieldDefault(row: CrudRow | null | undefined, field: CrudField) {
  let value = row?.[field.name];
  // API may return a nested object (e.g. row.employee) instead of the FK (row.employee_id)
  if (value == null && field.name.endsWith("_id")) {
    const rel = row?.[field.name.slice(0, -3)];
    if (rel && typeof rel === "object" && "id" in (rel as object)) {
      value = (rel as Record<string, unknown>).id;
    }
  }
  if (value == null) return field.defaultValue ?? "";
  // date inputs need YYYY-MM-DD; API returns ISO datetime with time component
  if (field.type === "date" && typeof value === "string" && value.includes("T")) {
    return value.split("T")[0];
  }
  return String(value);
}

type ApiErrors = Record<string, string[]>;

export function CrudModal({ open, onOpenChange, mode, title, description, resource, fields, row, onSaved, queueSubmit }: CrudModalProps) {
  const [saving, setSaving] = React.useState(false);
  const [errors, setErrors] = React.useState<ApiErrors>({});
  const formId = React.useId();

  const fieldRefMap = React.useMemo<Record<string, React.RefObject<HTMLInputElement | HTMLTextAreaElement | null>>>(() => {
    const map: Record<string, React.RefObject<HTMLInputElement | HTMLTextAreaElement | null>> = {};
    fields.forEach((f) => { map[f.name] = React.createRef<HTMLInputElement | HTMLTextAreaElement>(); });
    return map;
  }, [fields]);
  const searchDropdownOpen = React.useRef(false);

  // Cierra limpiando errores (sin efecto: el cierre siempre pasa por aqui).
  const handleOpenChange = React.useCallback(
    (next: boolean) => {
      if (!next) setErrors({});
      onOpenChange(next);
    },
    [onOpenChange],
  );

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);

    // Check if there is a real file to upload.
    const fileField = fields.find((f) => f.type === "file");
    const uploadedFile = fileField ? formData.get(fileField.name) : null;
    const hasFile = uploadedFile instanceof File && uploadedFile.size > 0;

    // Build JSON-serializable payload (file fields excluded; ownership fields excluded in edit).
    function buildPayload() {
      return Object.fromEntries(
        fields
          .filter((f) => f.type !== "file" && !(mode === "edit" && f.editReadonly))
          .map((field) => [field, normalizeValue(formData.get(field.name), field)] as const)
          .filter(([field, value]) => value !== null || !field.omitWhenEmpty)
          .map(([field, value]) => [field.name, value] as const),
      );
    }

    // Remove empty file inputs so FormData only carries the actual file.
    if (fileField && !hasFile) formData.delete(fileField.name);

    // F1: validar campos visibles obligatorios (reemplaza validación nativa perdida con noValidate)
    const VISIBLE_TYPES = new Set(["text", "email", "password", "url", "date", "time", "datetime-local", "number", "textarea"]);
    const clientErrors: ApiErrors = {};
    for (const f of fields) {
      if (!VISIBLE_TYPES.has(f.type ?? "text")) continue;
      if (mode === "edit" && f.editReadonly) continue;
      if (mode === "create" && f.createHidden) continue;
      const raw = String(formData.get(f.name) ?? "").trim();
      if (!raw) { if (f.required) clientErrors[f.name] = ["Este campo es requerido"]; continue; }
      if (f.type === "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(raw))
        clientErrors[f.name] = ["Ingresa un correo electrónico válido"];
      if (f.type === "url") { try { new URL(raw); } catch { clientErrors[f.name] = ["Ingresa una URL válida"]; } }
    }

    // CA-13: validar campos relation-search requeridos antes de enviar
    for (const f of fields.filter((f) => f.type === "relation-search" && f.required && !formData.get(f.name))) {
      clientErrors[f.name] = ["Este campo es requerido"];
    }

    // Validar selects requeridos (incluyendo binary-status selects)
    for (const f of fields.filter((f) => f.type === "select" && f.required && !(mode === "edit" && f.editReadonly) && !formData.get(f.name))) {
      clientErrors[f.name] = ["Este campo es requerido"];
    }

    if (Object.keys(clientErrors).length > 0) {
      setErrors(clientErrors);
      toast.error("Hay campos por corregir. Revisa lo marcado en rojo.");
      return;
    }

    setSaving(true);
    setErrors({});
    try {
      if (mode === "create" && queueSubmit) {
        await queueSubmit(buildPayload());
        toast.success("Registro encolado en modo contingencia. Se sincronizara al restablecer la conexion.");
        onSaved();
        handleOpenChange(false);
        return;
      }

      if (hasFile) {
        // Multipart submission — file upload (create only).
        const response = await api.post(resource, formData);
        if (response.data?.temporary_password) {
          toast.info(`Contrasena temporal: ${response.data.temporary_password}`, { duration: 15000 });
        }
      } else if (mode === "edit" && row?.id) {
        await api.put(`${resource}/${row.id}`, buildPayload());
      } else {
        const response = await api.post(resource, buildPayload());
        if (response.data?.temporary_password) {
          toast.info(`Contrasena temporal: ${response.data.temporary_password}`, { duration: 15000 });
        }
      }

      toast.success(mode === "edit" ? "Registro actualizado" : "Registro creado");
      onSaved();
      handleOpenChange(false);
    } catch (error) {
      const response = (error as { response?: { status?: number; data?: { errors?: ApiErrors; message?: string } } }).response;
      if (response?.status === 422 && response.data?.errors) {
        setErrors(response.data.errors);
        toast.error("Hay campos por corregir. Revisa lo marcado en rojo.");
      } else {
        toast.error(response?.data?.message ?? "No se pudo guardar. Revisa los campos e intenta de nuevo.");
      }
    } finally {
      setSaving(false);
    }
  }

  function clearError(name: string) {
    setErrors((prev) => (prev[name] ? { ...prev, [name]: [] } : prev));
  }

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent onEscapeKeyDown={(e) => { if (searchDropdownOpen.current) e.preventDefault(); }}>
        <DialogHeader>
          <DialogTitle>{title}</DialogTitle>
          {description ? <DialogDescription>{description}</DialogDescription> : null}
        </DialogHeader>

        <form id={formId} key={`${mode}-${row?.id ?? "new"}`} className="grid gap-4 sm:grid-cols-2" onSubmit={submit} noValidate>
          {fields.filter((f) => !(f.createOnly && mode === "edit")).map((field) => {
            const err = errors[field.name]?.[0];
            const invalid = cn(err && "border-destructive focus-visible:border-destructive");
            const isReadonly = mode === "edit" && !!field.editReadonly;
            return (
              <label key={field.name} className={cn("space-y-1.5 text-sm", field.colSpan === "full" && "sm:col-span-2")}>
                <span className="font-medium">
                  {field.label}
                  {field.required ? <span className="text-destructive"> *</span> : null}
                </span>
                {mode === "create" && field.createHidden ? (
                  <>
                    <input type="hidden" name={field.name} value={field.defaultValue ?? "active"} />
                    <span className="inline-flex h-9 items-center rounded-md border border-input bg-muted px-3 text-sm text-muted-foreground">
                      {field.options?.find((o) => o.value === (field.defaultValue ?? "active"))?.label ?? (field.defaultValue ?? "Activo")}
                    </span>
                  </>
                ) : field.type === "file" ? (
                  <input
                    name={field.name}
                    type="file"
                    accept={field.accept}
                    required={field.required}
                    onChange={() => clearError(field.name)}
                    className={cn(
                      "block w-full text-sm text-foreground file:mr-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary-foreground hover:file:bg-primary/90",
                      invalid,
                    )}
                  />
                ) : field.type === "relation-select" ? (
                  <RelationSelectField
                    field={field}
                    defaultVal={fieldDefault(row, field)}
                    onClearError={() => clearError(field.name)}
                    errorClass={invalid}
                    disabled={isReadonly}
                  />
                ) : field.type === "relation-search" ? (
                  <RelationSearchField
                    field={field}
                    defaultVal={fieldDefault(row, field)}
                    disabled={isReadonly}
                    errorClass={invalid}
                    onClearError={() => clearError(field.name)}
                    onDropdownOpenChange={(isOpen) => { searchDropdownOpen.current = isOpen; }}
                  />
                ) : field.type === "select" ? (
                  <select
                    name={field.name}
                    required={field.required}
                    defaultValue={fieldDefault(row, field)}
                    disabled={isReadonly}
                    onChange={() => clearError(field.name)}
                    className={cn(
                      "flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none transition focus-visible:border-primary",
                      isReadonly && "cursor-not-allowed opacity-60",
                      invalid,
                    )}
                  >
                    <option value="">Seleccionar</option>
                    {field.options?.map((option) => (
                      <option key={option.value} value={option.value}>
                        {option.label}
                      </option>
                    ))}
                  </select>
                ) : field.type === "textarea" ? (
                  <VoiceInputWrapper fieldRef={fieldRefMap[field.name]} voice={field.voice}>
                    <textarea
                      name={field.name}
                      required={field.required}
                      placeholder={field.placeholder}
                      defaultValue={fieldDefault(row, field)}
                      onInput={() => clearError(field.name)}
                      className={cn(
                        "min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none transition placeholder:text-muted-foreground focus-visible:border-primary",
                        invalid,
                      )}
                    />
                  </VoiceInputWrapper>
                ) : (
                  <VoiceInputWrapper fieldRef={fieldRefMap[field.name]} voice={field.voice}>
                    <Input
                      name={field.name}
                      type={field.type ?? "text"}
                      required={field.required}
                      placeholder={field.placeholder}
                      defaultValue={fieldDefault(row, field)}
                      pattern={field.pattern}
                      title={field.hint}
                      min={field.min}
                      max={field.max}
                      step={field.step}
                      onInput={() => clearError(field.name)}
                      className={invalid || undefined}
                    />
                  </VoiceInputWrapper>
                )}
                {err ? (
                  <span className="block text-xs text-destructive">{err}</span>
                ) : field.hint ? (
                  <span className="block text-xs text-muted-foreground">{field.hint}</span>
                ) : null}
              </label>
            );
          })}
        </form>

        <DialogFooter>
          <Button type="button" variant="outline" onClick={() => handleOpenChange(false)} disabled={saving}>
            Cancelar
          </Button>
          <Button type="submit" form={formId} disabled={saving}>
            {saving ? "Guardando..." : mode === "edit" ? "Guardar cambios" : "Crear registro"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
