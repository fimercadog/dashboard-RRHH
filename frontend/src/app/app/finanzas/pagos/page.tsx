"use client";

import * as React from "react";
import { Badge, badgeVariant } from "@/components/ui/badge";
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
import { DataTable } from "@/components/data-table/data-table";
import { api, API_BASE } from "@/lib/api";
import { AppColumnDef, dateColumn } from "@/lib/table-types";
import { CashAccount, Payment } from "@/lib/types";
import { useApiTable } from "@/lib/use-api-table";
import { KpiCard } from "@/components/kpi-card";
import { CreditCard, Calendar } from "lucide-react";

const COP = (v: number) =>
  new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(v);

const PAYABLE_LABELS: Record<string, string> = {
  accounts_receivable: "CxC (cobro)",
  accounts_payable:    "CxP (pago)",
};
const METHOD_LABELS: Record<string, string> = {
  cash: "Efectivo", transfer: "Transferencia", check: "Cheque", card: "Tarjeta", other: "Otro",
};

type FormState = {
  payable_type: string;
  payable_id:   string;
  cash_account_id: string;
  amount:   string;
  date:     string;
  method:   string;
  reference: string;
};

const EMPTY_FORM: FormState = {
  payable_type: "accounts_receivable",
  payable_id:   "",
  cash_account_id: "",
  amount:   "",
  date:     new Date().toISOString().split("T")[0],
  method:   "transfer",
  reference: "",
};

type PaymentStats = { total: number; this_month: number };

export default function PagosPage() {
  const table = useApiTable<Payment>("/payments");
  const [accounts, setAccounts] = React.useState<CashAccount[]>([]);
  const [open, setOpen] = React.useState(false);
  const [stats, setStats] = React.useState<PaymentStats | null>(null);
  const [statsStatus, setStatsStatus] = React.useState<"loading" | "error" | "ready">("loading");
  const [form, setForm] = React.useState<FormState>(EMPTY_FORM);
  const [submitting, setSubmitting] = React.useState(false);
  const [formError, setFormError] = React.useState<string | null>(null);
  const [cancelTarget, setCancelTarget] = React.useState<Payment | null>(null);
  const [cancelling, setCancelling] = React.useState(false);

  React.useEffect(() => {
    api.get<{ data: CashAccount[] }>("/cash-accounts?per_page=100")
      .then(r => setAccounts((r.data.data ?? []).filter(a => a.status === "active")));
    api.get<PaymentStats>("/payments/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

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
      table.refresh();
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
      table.refresh();
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

  const columns: AppColumnDef<Payment>[] = [
    dateColumn<Payment>("date", "Fecha"),
    { header: "Tipo", cell: ({ row }) => PAYABLE_LABELS[row.original.payable_type] ?? row.original.payable_type },
    { header: "Monto", cell: ({ row }) => <span className="font-medium tabular-nums">{COP(row.original.amount)}</span> },
    { header: "Método", cell: ({ row }) => METHOD_LABELS[row.original.method] ?? row.original.method },
    { header: "Cuenta caja", cell: ({ row }) => row.original.cash_account?.name ?? "-" },
    { accessorKey: "reference", header: "Referencia" },
    {
      header: "Estado",
      cell: ({ row }) => (
        <Badge variant={badgeVariant(row.original.status)}>
          {row.original.status === "active" ? "Activo" : "Cancelado"}
        </Badge>
      ),
    },
    {
      id: "actions",
      header: "",
      cell: ({ row }) =>
        row.original.status === "active" ? (
          <div className="flex justify-end">
            <Button
              size="sm"
              variant="outline"
              className="text-destructive hover:text-destructive hover:border-destructive"
              onClick={() => setCancelTarget(row.original)}
            >
              Cancelar
            </Button>
          </div>
        ) : null,
    },
  ];

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-2">
        <KpiCard label="Total pagos activos" value={stats?.total ?? null} icon={CreditCard} status={statsStatus} />
        <KpiCard label="Este mes" value={stats?.this_month ?? null} icon={Calendar} status={statsStatus} />
      </div>
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Pagos y cobros</h1>
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

      <DataTable
        columns={columns}
        data={table.data}
        search={table.search}
        onSearchChange={table.setSearch}
        page={table.page}
        onPageChange={table.setPage}
        perPage={table.perPage}
        onPerPageChange={table.setPerPage}
        exportBaseUrl={`${API_BASE}/exports/payments`}
        loading={table.loading}
        error={table.error}
      />

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
