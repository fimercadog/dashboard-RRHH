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
import { CashAccount, Payment } from "@/lib/types";

const COP = (v: number) =>
  new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(v);

const PAYABLE_LABELS: Record<string, string> = {
  accounts_receivable: "CxC (cobro cliente)",
  accounts_payable: "CxP (pago proveedor)",
};

const METHOD_LABELS: Record<string, string> = {
  cash: "Efectivo",
  transfer: "Transferencia",
  check: "Cheque",
  card: "Tarjeta",
  other: "Otro",
};

type FormState = {
  payable_type: string;
  payable_id: string;
  cash_account_id: string;
  amount: string;
  date: string;
  method: string;
  reference: string;
};

const EMPTY_FORM: FormState = {
  payable_type: "accounts_receivable",
  payable_id: "",
  cash_account_id: "",
  amount: "",
  date: new Date().toISOString().split("T")[0],
  method: "cash",
  reference: "",
};

export default function PagosPage() {
  const [payments, setPayments] = useState<Payment[]>([]);
  const [accounts, setAccounts] = useState<CashAccount[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState<FormState>(EMPTY_FORM);
  const [submitting, setSubmitting] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);
  const [cancelTarget, setCancelTarget] = useState<Payment | null>(null);
  const [cancelling, setCancelling] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [paymentsRes, accountsRes] = await Promise.all([
        api.get<{ data: Payment[] }>("/payments?per_page=50"),
        api.get<{ data: CashAccount[] }>("/cash-accounts?per_page=100"),
      ]);
      setPayments(paymentsRes.data.data ?? []);
      setAccounts((accountsRes.data.data ?? []).filter((a) => a.status === "active"));
    } catch {
      setError("No se pudieron cargar los pagos.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { load(); }, [load]);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setFormError(null);
    if (!form.payable_id || !form.cash_account_id || !form.amount) {
      setFormError("Completa todos los campos requeridos.");
      return;
    }
    setSubmitting(true);
    try {
      await api.post("/payments", {
        payable_type:    form.payable_type,
        payable_id:      Number(form.payable_id),
        cash_account_id: Number(form.cash_account_id),
        amount:          Number(form.amount),
        date:            form.date,
        method:          form.method,
        reference:       form.reference || undefined,
      });
      setOpen(false);
      setForm(EMPTY_FORM);
      await load();
    } catch (err: unknown) {
      const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
      setFormError(msg ?? "Error al registrar el pago.");
    } finally {
      setSubmitting(false);
    }
  }

  async function handleCancel() {
    if (!cancelTarget) return;
    setCancelling(true);
    try {
      await api.post(`/payments/${cancelTarget.id}/cancel`, {});
      setCancelTarget(null);
      await load();
    } catch (err: unknown) {
      const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
      alert(msg ?? "Error al cancelar el pago.");
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
          <h1 className="text-2xl font-bold">Pagos y cobros</h1>
          <p className="text-sm text-muted-foreground">Registra pagos a proveedores (CxP) y cobros de clientes (CxC).</p>
        </div>
        <Dialog open={open} onOpenChange={(v) => { setOpen(v); if (!v) { setForm(EMPTY_FORM); setFormError(null); } }}>
          <DialogTrigger asChild>
            <Button>Registrar pago / cobro</Button>
          </DialogTrigger>
          <DialogContent className="max-w-md">
            <DialogHeader>
              <DialogTitle>Registrar pago / cobro</DialogTitle>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-3">
              {field("Tipo de cuenta *",
                <select
                  className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  value={form.payable_type}
                  onChange={(e) => setForm((f) => ({ ...f, payable_type: e.target.value }))}
                >
                  <option value="accounts_receivable">CxC (cobro a cliente)</option>
                  <option value="accounts_payable">CxP (pago a proveedor)</option>
                </select>
              )}
              {field("ID de la CxC / CxP *",
                <Input type="number" min={1} placeholder="ID numérico" value={form.payable_id}
                  onChange={(e) => setForm((f) => ({ ...f, payable_id: e.target.value }))} required />
              )}
              {field("Cuenta de efectivo *",
                <select
                  className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  value={form.cash_account_id}
                  onChange={(e) => setForm((f) => ({ ...f, cash_account_id: e.target.value }))}
                  required
                >
                  <option value="">Seleccionar cuenta...</option>
                  {accounts.map((a) => (
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
              {field("Método *",
                <select
                  className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  value={form.method}
                  onChange={(e) => setForm((f) => ({ ...f, method: e.target.value }))}
                >
                  {Object.entries(METHOD_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
                </select>
              )}
              {field("Referencia",
                <Input value={form.reference} onChange={(e) => setForm((f) => ({ ...f, reference: e.target.value }))} placeholder="Nro. transacción, cheque..." />
              )}
              {formError && <p className="text-sm text-destructive">{formError}</p>}
              <DialogFooter>
                <Button type="submit" disabled={submitting}>{submitting ? "Registrando..." : "Registrar"}</Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {loading && <p className="text-muted-foreground text-sm">Cargando...</p>}
      {error && <p className="text-destructive text-sm">{error}</p>}

      {!loading && !error && payments.length === 0 && (
        <div className="rounded-lg border border-dashed p-10 text-center text-muted-foreground">
          No hay pagos registrados aún.
        </div>
      )}

      {!loading && payments.length > 0 && (
        <div className="rounded-lg border overflow-auto">
          <table className="w-full text-sm">
            <thead className="border-b bg-muted/50">
              <tr>
                {["Fecha", "Tipo", "ID Cuenta", "Monto", "Método", "Cuenta caja", "Referencia", "Estado", ""].map((h) => (
                  <th key={h} className="px-4 py-3 text-left font-medium text-muted-foreground">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {payments.map((p) => (
                <tr key={p.id} className="border-b last:border-0 hover:bg-muted/30">
                  <td className="px-4 py-3">{p.date as unknown as string}</td>
                  <td className="px-4 py-3 text-xs">{PAYABLE_LABELS[p.payable_type] ?? p.payable_type}</td>
                  <td className="px-4 py-3">#{p.payable_id}</td>
                  <td className="px-4 py-3 font-medium">{COP(p.amount)}</td>
                  <td className="px-4 py-3">{METHOD_LABELS[p.method] ?? p.method}</td>
                  <td className="px-4 py-3">{p.cash_account?.name ?? "-"}</td>
                  <td className="px-4 py-3">{p.reference ?? "-"}</td>
                  <td className="px-4 py-3">
                    <Badge className={p.status === "active" ? "" : "bg-muted text-muted-foreground"}>
                      {p.status === "active" ? "Activo" : "Cancelado"}
                    </Badge>
                  </td>
                  <td className="px-4 py-3">
                    {p.status === "active" && (
                      <Button size="sm" variant="destructive" onClick={() => setCancelTarget(p)}>Cancelar</Button>
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
          <DialogHeader><DialogTitle>Cancelar pago</DialogTitle></DialogHeader>
          <p className="text-sm">
            ¿Confirmas la cancelación del pago de <strong>{cancelTarget ? COP(cancelTarget.amount) : ""}</strong>?
            Los saldos de la cuenta y la CxC/CxP serán revertidos.
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
