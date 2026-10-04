"use client";

import React, { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { api } from "@/lib/api";
import { ChartOfAccount } from "@/lib/types";

const TYPE_LABEL: Record<string, string> = {
  asset: "Activo", liability: "Pasivo", equity: "Patrimonio",
  revenue: "Ingreso", expense: "Gasto", cost: "Costo",
};

const TYPE_CLASS: Record<string, string> = {
  asset:     "bg-blue-100 text-blue-800",
  liability: "bg-orange-100 text-orange-800",
  equity:    "bg-purple-100 text-purple-800",
  revenue:   "bg-green-100 text-green-800",
  expense:   "bg-red-100 text-red-800",
  cost:      "bg-yellow-100 text-yellow-800",
};

const ACCOUNT_TYPES = ["asset","liability","equity","revenue","expense","cost"] as const;
const NATURE_OPTIONS = [{ value: "debit", label: "Débito" }, { value: "credit", label: "Crédito" }];

const BLANK: Partial<ChartOfAccount> = {
  code: "", name: "", type: "asset", nature: "debit",
  parent_id: null, level: 3, allows_movements: true, status: "active", notes: "",
};

export default function PlanCuentasPage() {
  const [accounts, setAccounts] = useState<ChartOfAccount[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState("");
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<ChartOfAccount | null>(null);
  const [form, setForm] = useState(BLANK);
  const [saving, setSaving] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  const load = () => {
    setLoading(true);
    api.get<{ data: ChartOfAccount[] }>("/chart-of-accounts?per_page=200")
      .then((r) => setAccounts(r.data.data))
      .catch(() => setError("Error al cargar el plan de cuentas."))
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, []);

  const filtered = accounts.filter((a) =>
    !search || a.code.includes(search) || a.name.toLowerCase().includes(search.toLowerCase())
  );

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
      setOpen(false); load();
    } catch (e: any) {
      setFormError(e?.response?.data?.message || "Error al guardar.");
    } finally {
      setSaving(false);
    }
  };

  const toggleStatus = async (a: ChartOfAccount) => {
    const newStatus = a.status === "active" ? "inactive" : "active";
    await api.put(`/chart-of-accounts/${a.id}`, { status: newStatus }).catch(() => {});
    load();
  };

  return (
    <div className="mx-auto max-w-5xl space-y-6 p-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Plan de Cuentas</h1>
          <p className="text-sm text-muted-foreground">Estructura jerárquica de cuentas contables</p>
        </div>
        <Button onClick={openNew}>+ Nueva cuenta</Button>
      </div>

      <Input
        placeholder="Buscar por código o nombre..."
        value={search}
        onChange={(e) => setSearch(e.target.value)}
        className="max-w-sm"
      />

      {loading && <p className="text-sm text-muted-foreground">Cargando...</p>}
      {error && <p className="text-sm text-destructive">{error}</p>}

      {!loading && !error && (
        <div className="rounded-lg border">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/40">
                <th className="px-4 py-2 text-left font-medium">Código</th>
                <th className="px-4 py-2 text-left font-medium">Nombre</th>
                <th className="px-4 py-2 text-left font-medium">Tipo</th>
                <th className="px-4 py-2 text-left font-medium">Naturaleza</th>
                <th className="px-4 py-2 text-left font-medium">Movimientos</th>
                <th className="px-4 py-2 text-left font-medium">Estado</th>
                <th className="px-4 py-2" />
              </tr>
            </thead>
            <tbody>
              {filtered.length === 0 && (
                <tr>
                  <td colSpan={7} className="px-4 py-8 text-center text-muted-foreground">
                    {search ? "Sin resultados para la búsqueda." : "No hay cuentas registradas. Crea la primera."}
                  </td>
                </tr>
              )}
              {filtered.map((a) => (
                <tr key={a.id} className="border-b last:border-0 hover:bg-muted/20">
                  <td className="px-4 py-2 font-mono font-medium" style={{ paddingLeft: `${(a.level - 1) * 1.5 + 1}rem` }}>
                    {a.code}
                  </td>
                  <td className="px-4 py-2">{a.name}</td>
                  <td className="px-4 py-2">
                    <Badge className={TYPE_CLASS[a.type]}>{TYPE_LABEL[a.type]}</Badge>
                  </td>
                  <td className="px-4 py-2 capitalize">{a.nature === "debit" ? "Débito" : "Crédito"}</td>
                  <td className="px-4 py-2">{a.allows_movements ? "✓" : "—"}</td>
                  <td className="px-4 py-2">
                    <Badge className={a.status === "active" ? "" : "bg-muted text-muted-foreground"}>
                      {a.status === "active" ? "Activa" : "Inactiva"}
                    </Badge>
                  </td>
                  <td className="px-4 py-2">
                    <div className="flex gap-2">
                      <Button variant="ghost" size="sm" onClick={() => openEdit(a)}>Editar</Button>
                      <Button variant="ghost" size="sm" onClick={() => toggleStatus(a)}>
                        {a.status === "active" ? "Inactivar" : "Activar"}
                      </Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

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
            <div className="flex justify-end gap-2 pt-2">
              <Button variant="outline" onClick={() => setOpen(false)}>Cancelar</Button>
              <Button onClick={save} disabled={saving}>{saving ? "Guardando..." : "Guardar"}</Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
}
