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

export function CrudModal({ open, onOpenChange, mode, title, description, resource, fields, row, onSaved }: CrudModalProps) {
  const [saving, setSaving] = React.useState(false);
  const formId = React.useId();

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    const entries = fields
      .map((field) => [field, normalizeValue(formData.get(field.name), field)] as const)
      .filter(([field, value]) => value !== null || !field.omitWhenEmpty)
      .map(([field, value]) => [field.name, value] as const);
    const payload = Object.fromEntries(entries);

    setSaving(true);
    try {
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
      onOpenChange(false);
    } catch {
      toast.error("No se pudo guardar. Revisa los campos e intenta de nuevo.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{title}</DialogTitle>
          {description ? <DialogDescription>{description}</DialogDescription> : null}
        </DialogHeader>

        <form id={formId} className="grid gap-4 sm:grid-cols-2" onSubmit={submit}>
          {fields.map((field) => (
            <label key={field.name} className={cn("space-y-2 text-sm", field.colSpan === "full" && "sm:col-span-2")}>
              <span className="font-medium">{field.label}</span>
              {field.type === "select" ? (
                <select
                  name={field.name}
                  required={field.required}
                  defaultValue={fieldDefault(row, field)}
                  className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none transition focus-visible:border-primary"
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
                  className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none transition placeholder:text-muted-foreground focus-visible:border-primary"
                />
              ) : (
                <Input
                  name={field.name}
                  type={field.type ?? "text"}
                  required={field.required}
                  placeholder={field.placeholder}
                  defaultValue={fieldDefault(row, field)}
                />
              )}
            </label>
          ))}
        </form>

        <DialogFooter>
          <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={saving}>
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
