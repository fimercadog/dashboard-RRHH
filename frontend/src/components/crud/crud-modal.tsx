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
import { api } from "@/lib/api";
import { cn } from "@/lib/utils";

type FieldOption = {
  label: string;
  value: string | number;
};

export type CrudField = {
  name: string;
  label: string;
  type?: "text" | "email" | "password" | "date" | "time" | "number" | "select" | "textarea" | "relation-select" | "file";
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
  if (field.type === "number" || field.type === "relation-select") return Number(value);
  return String(value);
}

type RelationOption = { id: number; label: string };

function RelationSelectField({
  field,
  defaultVal,
  onClearError,
  errorClass,
}: {
  field: CrudField;
  defaultVal: string;
  onClearError: () => void;
  errorClass?: string;
}) {
  const [options, setOptions] = React.useState<RelationOption[]>([]);
  const [value, setValue] = React.useState(defaultVal);

  React.useEffect(() => {
    setValue(defaultVal);
  }, [defaultVal]);

  React.useEffect(() => {
    if (!field.endpoint) return;
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
    });
  }, [field.endpoint]);

  return (
    <select
      name={field.name}
      required={field.required}
      value={value}
      onChange={(e) => {
        setValue(e.target.value);
        onClearError();
      }}
      className={cn(
        "flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none transition focus-visible:border-primary",
        errorClass,
      )}
    >
      <option value="">Seleccionar</option>
      {options.map((opt) => (
        <option key={opt.id} value={opt.id}>
          {opt.label}
        </option>
      ))}
    </select>
  );
}

function fieldDefault(row: CrudRow | null | undefined, field: CrudField) {
  const value = row?.[field.name];
  if (value == null) return field.defaultValue ?? "";
  return String(value);
}

type ApiErrors = Record<string, string[]>;

export function CrudModal({ open, onOpenChange, mode, title, description, resource, fields, row, onSaved, queueSubmit }: CrudModalProps) {
  const [saving, setSaving] = React.useState(false);
  const [errors, setErrors] = React.useState<ApiErrors>({});
  const formId = React.useId();

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

    // Build JSON-serializable payload (file fields excluded).
    function buildPayload() {
      return Object.fromEntries(
        fields
          .filter((f) => f.type !== "file")
          .map((field) => [field, normalizeValue(formData.get(field.name), field)] as const)
          .filter(([field, value]) => value !== null || !field.omitWhenEmpty)
          .map(([field, value]) => [field.name, value] as const),
      );
    }

    // Remove empty file inputs so FormData only carries the actual file.
    if (fileField && !hasFile) formData.delete(fileField.name);

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
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{title}</DialogTitle>
          {description ? <DialogDescription>{description}</DialogDescription> : null}
        </DialogHeader>

        <form id={formId} className="grid gap-4 sm:grid-cols-2" onSubmit={submit} noValidate={false}>
          {fields.filter((f) => !(f.createOnly && mode === "edit")).map((field) => {
            const err = errors[field.name]?.[0];
            const invalid = cn(err && "border-destructive focus-visible:border-destructive");
            return (
              <label key={field.name} className={cn("space-y-1.5 text-sm", field.colSpan === "full" && "sm:col-span-2")}>
                <span className="font-medium">
                  {field.label}
                  {field.required ? <span className="text-destructive"> *</span> : null}
                </span>
                {field.type === "file" ? (
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
                  />
                ) : field.type === "select" ? (
                  <select
                    name={field.name}
                    required={field.required}
                    defaultValue={fieldDefault(row, field)}
                    onChange={() => clearError(field.name)}
                    className={cn(
                      "flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none transition focus-visible:border-primary",
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
                ) : (
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
