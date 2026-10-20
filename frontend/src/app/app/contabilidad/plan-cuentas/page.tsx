"use client";

import * as React from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge, badgeVariant, type BadgeVariant } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { DataTable } from "@/components/data-table/data-table";
import { api, API_BASE } from "@/lib/api";
import { AppColumnDef } from "@/lib/table-types";
import { ChartOfAccount } from "@/lib/types";
import { useApiTable } from "@/lib/use-api-table";

const TYPE_LABEL: Record<string, string> = {
  asset: "Activo", liability: "Pasivo", equity: "Patrimonio",
  revenue: "Ingreso", expense: "Gasto", cost: "Costo",
};

const TYPE_VARIANT: Record<string, BadgeVariant> = {
  asset:     "info",
  liability: "warning",
  equity:    "primary",
  revenue:   "success",
  expense:   "danger",
  cost:      "muted",
};

const ACCOUNT_TYPES = ["asset","liability","equity","revenue","expense","cost"] as const;
const NATURE_OPTIONS = [{ value: "debit", label: "Débito" }, { value: "credit", label: "Crédito" }];

const BLANK: Partial<ChartOfAccount> = {
  code: "", name: "", type: "asset", nature: "debit",
  parent_id: null, level: 3, allows_movements: true, status: "active", notes: "",
};

export default function PlanCuentasPage() {
  const table = useApiTable<ChartOfAccount>("/chart-of-accounts");
  const [open, setOpen] = React.useState(false);
  const [editing, setEditing] = React.useState<ChartOfAccount | null>(null);
  const [form, setForm] = React.useState(BLANK);
  const [saving, setSaving] = React.useState(false);
  const [formError, setFormError] = React.useState<string | null>(null);

  const openNew = () => { setEditing(null); setForm(BLANK); setFormError(null); setOpen(true); };
  const openEdit = (a: ChartOfAccount) => { setEditing(a); setForm({ ...a }); setFormError(null); setOpen(true); };

  const save = async () => {
    setSaving(true); setFormError(null);
    try {
      if (editing) {
        await api.put(`/chart-of-accounts/${editing.id}`, form);
      } else {
        await api.post("/chart-of-accounts", form);
      }
      setOpen(false); table.refresh();
    } catch (e: any) {
      setFormError(e?.response?.data?.message || "Error al guardar.");
    } finally {
      setSaving(false);
    }
  };

  const toggleStatus = async (a: ChartOfAccount) => {
    const newStatus = a.status === "active" ? "inactive" : "active";
    await api.put(`/chart-of-accounts/${a.id}`, { status: newStatus }).catch(() => {});
    table.refresh();
  };

  const columns: AppColumnDef<ChartOfAccount>[] = [
    {
      accessorKey: "code",
      header: "Código",
      cell: ({ row }) => (
        <span
          className="font-mono font-medium"
          style={{ paddingLeft: `${((row.original.level ?? 1) - 1) * 1.5}rem` }}
        >
          {row.original.code}
        </span>
      ),
    },
    { accessorKey: "name", header: "Nombre" },
    {
      header: "Tipo",
      cell: ({ row }) => (
        <Badge variant={TYPE_VARIANT[row.original.type] ?? "default"}>{TYPE_LABEL[row.original.type]}</Badge>
      ),
    },
    {
      header: "Naturaleza",
      cell: ({ row }) => row.original.nature === "debit" ? "Débito" : "Crédito",
    },
    {
      header: "Movimientos",
      cell: ({ row }) => row.original.allows_movements ? "✓" : "—",
    },
    {
      header: "Estado",
      cell: ({ row }) => (
        <Badge variant={badgeVariant(row.original.status)}>
          {row.original.status === "active" ? "Activa" : "Inactiva"}
        </Badge>
      ),
    },
    {
      id: "actions",
      header: "",
      cell: ({ row }) => (
        <div className="flex justify-end gap-2">
          <Button variant="ghost" size="sm" onClick={() => openEdit(row.original)}>Editar</Button>
          <Button variant="ghost" size="sm" onClick={() => toggleStatus(row.original)}>
            {row.original.status === "active" ? "Inactivar" : "Activar"}
          </Button>
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Plan de Cuentas</h1>
          <p className="text-sm text-muted-foreground">Estructura jerárquica de cuentas contables</p>
        </div>
        <Button onClick={openNew}>+ Nueva cuenta</Button>
      </div>

      <DataTable
        columns={columns}
        data={table.data}
        search={table.search}
        onSearchChange={table.setSearch}
        page={table.page}
        onPageChange={table.setPage}
        perPage={table.perPage}
        onPerPageChange={table.setPerPage}
        exportBaseUrl={`${API_BASE}/exports/chart-of-accounts`}
        loading={table.loading}
        error={table.error}
      />

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>{editing ? "Editar cuenta" : "Nueva cuenta contable"}</DialogTitle>
          </DialogHeader>
          <div className="space-y-3">
            {formError && <p className="text-sm text-destructive">{formError}</p>}
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="text-xs font-medium">Código *</label>
                <Input value={form.code ?? ""} onChange={(e) => setForm({ ...form, code: e.target.value })} placeholder="ej. 1105" />
              </div>
              <div>
                <label className="text-xs font-medium">Nivel</label>
                <Input type="number" min={1} max={8} value={form.level ?? 3} onChange={(e) => setForm({ ...form, level: +e.target.value })} />
              </div>
            </div>
            <div>
              <label className="text-xs font-medium">Nombre *</label>
              <Input value={form.name ?? ""} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder="ej. Caja" />
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="text-xs font-medium">Tipo *</label>
                <select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={form.type ?? "asset"} onChange={(e) => setForm({ ...form, type: e.target.value as any })}>
                  {ACCOUNT_TYPES.map((t) => <option key={t} value={t}>{TYPE_LABEL[t]}</option>)}
                </select>
              </div>
              <div>
                <label className="text-xs font-medium">Naturaleza *</label>
                <select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={form.nature ?? "debit"} onChange={(e) => setForm({ ...form, nature: e.target.value as any })}>
                  {NATURE_OPTIONS.map((n) => <option key={n.value} value={n.value}>{n.label}</option>)}
                </select>
              </div>
            </div>
            <div>
              <label className="text-xs font-medium">Cuenta padre (ID)</label>
              <Input type="number" value={form.parent_id ?? ""} onChange={(e) => setForm({ ...form, parent_id: e.target.value ? +e.target.value : null })} placeholder="Dejar vacío si es cuenta raíz" />
            </div>
            <div className="flex items-center gap-3">
              <input type="checkbox" id="allows" checked={form.allows_movements ?? false} onChange={(e) => setForm({ ...form, allows_movements: e.target.checked })} />
              <label htmlFor="allows" className="text-sm">Permite movimientos contables</label>
            </div>
            <div>
              <label className="text-xs font-medium">Notas</label>
              <Input value={form.notes ?? ""} onChange={(e) => setForm({ ...form, notes: e.target.value })} />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setOpen(false)}>Cancelar</Button>
            <Button onClick={save} disabled={saving}>{saving ? "Guardando..." : "Guardar"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
