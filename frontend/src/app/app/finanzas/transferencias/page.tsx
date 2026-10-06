"use client";

import { useCallback, useEffect, useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { api } from "@/lib/api";
import { CashAccount, Transfer } from "@/lib/types";

const COP = (v: number) =>
  new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(v);

type FormState = {
  from_account_id: string;
  to_account_id: string;
  amount: string;
  date: string;
  reference: string;
  notes: string;
};

const EMPTY_FORM: FormState = {
  from_account_id: "",
  to_account_id: "",
  amount: "",
  date: new Date().toISOString().split("T")[0],
  reference: "",
  notes: "",
};

export default function TransferenciasPage() {
  const [transfers, setTransfers] = useState<Transfer[]>([]);
  const [accounts, setAccounts] = useState<CashAccount[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState<FormState>(EMPTY_FORM);
  const [submitting, setSubmitting] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);
  const [cancelTarget, setCancelTarget] = useState<Transfer | null>(null);
  const [cancelling, setCancelling] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [transfersRes, accountsRes] = await Promise.all([
        api.get<{ data: Transfer[] }>("/transfers?per_page=50"),
        api.get<{ data: CashAccount[] }>("/cash-accounts?per_page=100"),
      ]);
      setTransfers(transfersRes.data.data ?? []);
      setAccounts((accountsRes.data.data ?? []).filter((a) => a.status === "active"));
    } catch {
      setError("No se pudieron cargar las transferencias.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { load(); }, [load]);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setFormError(null);
    if (!form.from_account_id || !form.to_account_id || !form.amount) {
      setFormError("Completa todos los campos requeridos.");
      return;
    }
    if (form.from_account_id === form.to_account_id) {
      setFormError("Las cuentas origen y destino deben ser diferentes.");
      return;
    }
    setSubmitting(true);
    try {
      await api.post("/transfers", {
        from_account_id: Number(form.from_account_id),
        to_account_id:   Number(form.to_account_id),
        amount:          Number(form.amount),
        date:            form.date,
        reference:       form.reference || undefined,
        notes:           form.notes || undefined,
      });
      setOpen(false);
      setForm(EMPTY_FORM);
      await load();
    } catch (err: unknown) {
      const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
      setFormError(msg ?? "Error al registrar la transferencia.");
    } finally {
      setSubmitting(false);
    }
  }

  async function handleCancel() {
    if (!cancelTarget) return;
    setCancelling(true);
    try {
      await api.post(`/transfers/${cancelTarget.id}/cancel`, {});
      setCancelTarget(null);
      await load();
    } catch (err: unknown) {
      const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
      alert(msg ?? "Error al cancelar la transferencia.");
    } finally {
      setCancelling(false);
    }
  }

  const field = (label: string, children: React.ReactNode) => (
    <div className="space-y-1">
      <label className="text-sm font-medium">{label}</label>
      {children}
    </div>
  );

  return (
    <div className="p-6 space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Transferencias</h1>
          <p className="text-sm text-muted-foreground">Mueve fondos entre cuentas de efectivo.</p>
        </div>
        <Dialog open={open} onOpenChange={(v) => { setOpen(v); if (!v) { setForm(EMPTY_FORM); setFormError(null); } }}>
          <DialogTrigger asChild>
            <Button>Nueva transferencia</Button>
          </DialogTrigger>
          <DialogContent className="max-w-md">
            <DialogHeader>
              <DialogTitle>Nueva transferencia</DialogTitle>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-3">
              {field("Cuenta origen *",
                <select
                  className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  value={form.from_account_id}
                  onChange={(e) => setForm((f) => ({ ...f, from_account_id: e.target.value }))}
                  required
                >
                  <option value="">Seleccionar...</option>
                  {accounts.map((a) => (
                    <option key={a.id} value={String(a.id)}>{a.name} — {COP(a.balance)}</option>
                  ))}
                </select>
              )}
              {field("Cuenta destino *",
                <select
                  className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  value={form.to_account_id}
                  onChange={(e) => setForm((f) => ({ ...f, to_account_id: e.target.value }))}
                  required
                >
                  <option value="">Seleccionar...</option>
                  {accounts
                    .filter((a) => String(a.id) !== form.from_account_id)
                    .map((a) => (
                      <option key={a.id} value={String(a.id)}>{a.name} — {COP(a.balance)}</option>
                    ))}
                </select>
              )}
              <div className="grid grid-cols-2 gap-3">
                {field("Monto *",
                  <Input type="number" min={0.01} step={0.01} placeholder="0.00" value={form.amount}
                    onChange={(e) => setForm((f) => ({ ...f, amount: e.target.value }))} required />
                )}
                {field("Fecha *",
                  <Input type="date" value={form.date} onChange={(e) => setForm((f) => ({ ...f, date: e.target.value }))} required />
                )}
              </div>
              {field("Referencia",
                <Input value={form.reference} onChange={(e) => setForm((f) => ({ ...f, reference: e.target.value }))} placeholder="Nro. comprobante..." />
              )}
              {field("Notas",
                <Input value={form.notes} onChange={(e) => setForm((f) => ({ ...f, notes: e.target.value }))} placeholder="Observaciones opcionales..." />
              )}
              {formError && <p className="text-sm text-destructive">{formError}</p>}
              <DialogFooter>
                <Button type="submit" disabled={submitting}>{submitting ? "Transfiriendo..." : "Transferir"}</Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {loading && <p className="text-muted-foreground text-sm">Cargando...</p>}
      {error && <p className="text-destructive text-sm">{error}</p>}

      {!loading && !error && transfers.length === 0 && (
        <div className="rounded-lg border border-dashed p-10 text-center text-muted-foreground">
          No hay transferencias registradas aún.
        </div>
      )}

      {!loading && transfers.length > 0 && (
        <div className="rounded-lg border overflow-auto">
          <table className="w-full text-sm">
            <thead className="border-b bg-muted/50">
              <tr>
                {["N.º", "Fecha", "Origen", "Destino", "Monto", "Referencia", "Notas", "Estado", ""].map((h) => (
                  <th key={h} className="px-4 py-3 text-left font-medium text-muted-foreground">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {transfers.map((t, i) => (
                <tr key={t.id} className="border-b last:border-0 hover:bg-muted/30">
                  <td className="px-3 py-3 text-xs tabular-nums text-muted-foreground">{i + 1}</td>
                  <td className="px-4 py-3">{t.date as unknown as string}</td>
                  <td className="px-4 py-3">{t.from_account?.name ?? `#${t.id}`}</td>
                  <td className="px-4 py-3">{t.to_account?.name ?? "-"}</td>
                  <td className="px-4 py-3 font-medium">{COP(t.amount)}</td>
                  <td className="px-4 py-3">{t.reference ?? "-"}</td>
                  <td className="px-4 py-3">{t.notes ?? "-"}</td>
                  <td className="px-4 py-3">
                    <Badge className={t.status === "active" ? "" : "bg-muted text-muted-foreground"}>
                      {t.status === "active" ? "Activa" : "Cancelada"}
                    </Badge>
                  </td>
                  <td className="px-4 py-3">
                    {t.status === "active" && (
                      <Button size="sm" variant="destructive" onClick={() => setCancelTarget(t)}>Cancelar</Button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <Dialog open={!!cancelTarget} onOpenChange={(v) => { if (!v) setCancelTarget(null); }}>
        <DialogContent>
          <DialogHeader><DialogTitle>Cancelar transferencia</DialogTitle></DialogHeader>
          <p className="text-sm">
            ¿Confirmas la cancelación de la transferencia de <strong>{cancelTarget ? COP(cancelTarget.amount) : ""}</strong>?
            Los saldos de ambas cuentas serán revertidos con transacciones compensatorias.
          </p>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCancelTarget(null)} disabled={cancelling}>No, mantener</Button>
            <Button variant="destructive" onClick={handleCancel} disabled={cancelling}>
              {cancelling ? "Cancelando..." : "Sí, cancelar"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
