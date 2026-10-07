"use client";

import React, { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { api } from "@/lib/api";
import { AccountingPeriod } from "@/lib/types";

const BLANK = { name: "", start_date: "", end_date: "" };

export default function PeriodosPage() {
  const [periods, setPeriods] = useState<AccountingPeriod[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState(BLANK);
  const [saving, setSaving] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);
  const [closing, setClosing] = useState<number | null>(null);

  const load = () => {
    setLoading(true);
    api.get<{ data: AccountingPeriod[] }>("/accounting-periods?per_page=50")
      .then((r) => setPeriods(r.data.data))
      .catch(() => setError("Error al cargar los períodos."))
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, []);

  const save = async () => {
    setSaving(true); setFormError(null);
    try {
      await api.post("/accounting-periods", form);
      setOpen(false); setForm(BLANK); load();
    } catch (e: any) {
      setFormError(e?.response?.data?.message || "Error al crear el período.");
    } finally {
      setSaving(false);
    }
  };

  const close = async (id: number) => {
    if (!confirm("¿Cerrar este período? Esta acción no se puede deshacer.")) return;
    setClosing(id);
    try {
      await api.post(`/accounting-periods/${id}/close`, {});
      load();
    } catch (e: any) {
      alert(e?.response?.data?.message || "Error al cerrar el período.");
    } finally {
      setClosing(null);
    }
  };

  return (
    <div className="mx-auto max-w-4xl space-y-6 p-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Períodos Contables</h1>
          <p className="text-sm text-muted-foreground">Gestión de períodos abiertos y cerrados</p>
        </div>
        <Button onClick={() => { setFormError(null); setForm(BLANK); setOpen(true); }}>+ Nuevo período</Button>
      </div>

      {loading && <p className="text-sm text-muted-foreground">Cargando...</p>}
      {error && <p className="text-sm text-destructive">{error}</p>}

      {!loading && !error && (
        <div className="rounded-lg border">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/40">
                <th className="w-10 px-3 py-2 text-left font-medium">N.º</th>
                <th className="px-4 py-2 text-left font-medium">Nombre</th>
                <th className="px-4 py-2 text-left font-medium">Inicio</th>
                <th className="px-4 py-2 text-left font-medium">Fin</th>
                <th className="px-4 py-2 text-left font-medium">Asientos</th>
                <th className="px-4 py-2 text-left font-medium">Estado</th>
                <th className="px-4 py-2" />
              </tr>
            </thead>
            <tbody>
              {periods.length === 0 && (
                <tr>
                  <td colSpan={7} className="px-4 py-8 text-center text-muted-foreground">
                    No hay períodos. Crea el primero para empezar a registrar asientos.
                  </td>
                </tr>
              )}
              {periods.map((p, i) => (
                <tr key={p.id} className="border-b last:border-0 odd:bg-background even:bg-muted/30 hover:bg-muted/50">
                  <td className="px-3 py-2 text-xs tabular-nums text-muted-foreground">{i + 1}</td>
                  <td className="px-4 py-2 font-medium">{p.name}</td>
                  <td className="px-4 py-2">{p.start_date}</td>
                  <td className="px-4 py-2">{p.end_date}</td>
                  <td className="px-4 py-2">{p.entries_count ?? "—"}</td>
                  <td className="px-4 py-2">
                    <Badge variant={badgeVariant(p.status)}>
                      {p.status === "open" ? "Abierto" : "Cerrado"}
                    </Badge>
                  </td>
                  <td className="px-4 py-2">
                    {p.status === "open" && (
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => close(p.id)}
                        disabled={closing === p.id}
                      >
                        {closing === p.id ? "Cerrando..." : "Cerrar"}
                      </Button>
                    )}
                    {p.status === "closed" && p.closed_at && (
                      <span className="text-xs text-muted-foreground">
                        Cerrado {new Date(p.closed_at).toLocaleDateString()}
                      </span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>Nuevo período contable</DialogTitle>
          </DialogHeader>
          <div className="space-y-3">
            {formError && <p className="text-sm text-destructive">{formError}</p>}
            <div>
              <label className="text-xs font-medium">Nombre *</label>
              <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder="ej. Enero 2026" />
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="text-xs font-medium">Fecha inicio *</label>
                <Input type="date" value={form.start_date} onChange={(e) => setForm({ ...form, start_date: e.target.value })} />
              </div>
              <div>
                <label className="text-xs font-medium">Fecha fin *</label>
                <Input type="date" value={form.end_date} onChange={(e) => setForm({ ...form, end_date: e.target.value })} />
              </div>
            </div>
            <div className="flex justify-end gap-2 pt-2">
              <Button variant="outline" onClick={() => setOpen(false)}>Cancelar</Button>
              <Button onClick={save} disabled={saving}>{saving ? "Creando..." : "Crear período"}</Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
}
