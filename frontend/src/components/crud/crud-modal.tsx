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
  type?: "text" | "email" | "password" | "date" | "time" | "number" | "select" | "textarea";
  required?: boolean;
  placeholder?: string;
  options?: FieldOption[];
  colSpan?: "full";
  /** Skip sending this field when left blank, instead of overwriting the stored value with null. */
  omitWhenEmpty?: boolean;
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
  if (value == null || value === "") return null;
  if (field.type === "number") return Number(value);
  return String(value);
}

function fieldDefault(row: CrudRow | null | undefined, field: CrudField) {
  const value = row?.[field.name];
  return value == null ? "" : String(value);
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
    const entries = fields
      .map((field) => [field, normalizeValue(formData.get(field.name), field)] as const)
      .filter(([field, value]) => value !== null || !field.omitWhenEmpty)
      .map(([field, value]) => [field.name, value] as const);
    const payload = Object.fromEntries(entries);

    setSaving(true);
    setErrors({});
    try {
      if (mode === "create" && queueSubmit) {
        await queueSubmit(payload);
        toast.success("Registro encolado en modo contingencia. Se sincronizara al restablecer la conexion.");
        onSaved();
        handleOpenChange(false);
        return;
      }

      if (mode === "edit" && row?.id) {
        await api.put(`${resource}/${row.id}`, payload);
      } else {
        const response = await api.post(resource, payload);
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
          {fields.map((field) => {
            const err = errors[field.name]?.[0];
            const invalid = cn(err && "border-destructive focus-visible:border-destructive");
            return (
              <label key={field.name} className={cn("space-y-1.5 text-sm", field.colSpan === "full" && "sm:col-span-2")}>
                <span className="font-medium">
                  {field.label}
                  {field.required ? <span className="text-destructive"> *</span> : null}
                </span>
                {field.type === "select" ? (
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
