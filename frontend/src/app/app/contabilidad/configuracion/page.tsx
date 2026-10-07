"use client";

import React, { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { api } from "@/lib/api";
import { AccountingAccountConfig, ChartOfAccount } from "@/lib/types";

const KEY_LABELS: Record<string, string> = {
  cxc_default:       "CxC (Cuentas por cobrar)",
  cxp_default:       "CxP (Cuentas por pagar)",
  inventory_default: "Inventario",
  cogs_default:      "Costo de ventas (COGS)",
  revenue_default:   "Ingresos por ventas",
  net_income:        "Utilidad del ejercicio",
  retained_earnings: "Utilidades acumuladas",
};

export default function ConfiguracionContabilidadPage() {
  const [configs, setConfigs] = useState<AccountingAccountConfig[]>([]);
  const [accounts, setAccounts] = useState<ChartOfAccount[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<AccountingAccountConfig | null>(null);
  const [form, setForm] = useState({ config_key: "", account_id: "" });
  const [saving, setSaving] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  const load = () => {
    setLoading(true);
    Promise.all([
      api.get<{ data: AccountingAccountConfig[] }>("/accounting-configs"),
      api.get<{ data: ChartOfAccount[] }>("/chart-of-accounts?per_page=200&allows_movements=1"),
    ])
      .then(([c, a]) => { setConfigs(c.data.data); setAccounts(a.data.data); })
      .catch(() => setError("Error al cargar la configuración."))
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, []);

  const openEdit = (c: AccountingAccountConfig) => {
    setEditing(c);
    setForm({ config_key: c.config_key, account_id: String(c.account_id) });
    setFormError(null);
    setOpen(true);
  };

  const openNew = () => {
    setEditing(null);
    setForm({ config_key: "", account_id: "" });
    setFormError(null);
    setOpen(true);
  };

  const save = async () => {
    setSaving(true); setFormError(null);
    try {
      const payload = { config_key: form.config_key, account_id: Number(form.account_id) };
      if (editing) {
        await api.put(`/accounting-configs/${editing.id}`, payload);
      } else {
        await api.post("/accounting-configs", payload);
      }
      setOpen(false); load();
    } catch (e: any) {
      setFormError(e?.response?.data?.message || "Error al guardar.");
    } finally {
      setSaving(false);
    }
  };

  const movableAccounts = accounts.filter((a) => a.allows_movements && a.status === "active");

  return (
    <div className="mx-auto max-w-3xl space-y-6 p-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Configuración Contable</h1>
          <p className="text-sm text-muted-foreground">Mapeo de claves de integración a cuentas del PUC</p>
        </div>
        <Button onClick={openNew}>+ Nueva clave</Button>
      </div>

      {loading && <p className="text-sm text-muted-foreground">Cargando...</p>}
      {error && <p className="text-sm text-destructive">{error}</p>}

      {!loading && !error && (
        <div className="rounded-lg border">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/40">
                <th className="px-4 py-2 text-left font-medium">Clave</th>
                <th className="px-4 py-2 text-left font-medium">Descripción</th>
                <th className="px-4 py-2 text-left font-medium">Cuenta asignada</th>
                <th className="px-4 py-2" />
              </tr>
            </thead>
            <tbody>
              {configs.length === 0 && (
                <tr>
                  <td colSpan={4} className="px-4 py-8 text-center text-muted-foreground">
                    Sin configuración. Ejecuta el seeder o crea las claves manualmente.
                  </td>
                </tr>
              )}
              {configs.map((c) => (
                <tr key={c.id} className="border-b last:border-0 odd:bg-background even:bg-muted/30 hover:bg-muted/50">
                  <td className="px-4 py-2 font-mono text-xs">{c.config_key}</td>
                  <td className="px-4 py-2 text-muted-foreground">{KEY_LABELS[c.config_key] ?? "—"}</td>
                  <td className="px-4 py-2">
                    {c.account_code && (
                      <span className="font-mono text-xs">{c.account_code}</span>
                    )}{" "}
                    {c.account_name}
                  </td>
                  <td className="px-4 py-2">
                    <Button variant="ghost" size="sm" onClick={() => openEdit(c)}>Editar</Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <div className="rounded-md border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
        <strong>Nota:</strong> Estas claves conectan las operaciones de Compras, Ventas y Finanzas con el plan de cuentas.
        Cambiar una clave afecta los asientos generados automáticamente a partir de ese momento.
        Los asientos ya publicados no se modifican.
      </div>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>{editing ? "Editar configuración" : "Nueva clave de configuración"}</DialogTitle>
          </DialogHeader>
          <div className="space-y-3">
            {formError && <p className="text-sm text-destructive">{formError}</p>}
            <div>
              <label className="text-xs font-medium">Clave *</label>
              {editing ? (
                <Input value={form.config_key} disabled className="bg-muted" />
              ) : (
                <select
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                  value={form.config_key}
                  onChange={(e) => setForm({ ...form, config_key: e.target.value })}
                >
                  <option value="">Seleccionar clave...</option>
                  {Object.entries(KEY_LABELS).map(([k, label]) => (
                    <option key={k} value={k}>{k} — {label}</option>
                  ))}
                </select>
              )}
            </div>
            <div>
              <label className="text-xs font-medium">Cuenta contable *</label>
              <select
                className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                value={form.account_id}
                onChange={(e) => setForm({ ...form, account_id: e.target.value })}
              >
                <option value="">Seleccionar cuenta...</option>
                {movableAccounts.map((a) => (
                  <option key={a.id} value={a.id}>{a.code} — {a.name}</option>
                ))}
              </select>
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
