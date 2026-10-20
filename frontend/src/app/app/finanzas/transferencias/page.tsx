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
import { CashAccount, Transfer } from "@/lib/types";
import { useApiTable } from "@/lib/use-api-table";

const COP = (v: number) =>
  new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(v);

type FormState = {
  from_account_id: string;
  to_account_id:   string;
  amount:    string;
  date:      string;
  reference: string;
  notes:     string;
};

const EMPTY_FORM: FormState = {
  from_account_id: "",
  to_account_id:   "",
  amount:    "",
  date:      new Date().toISOString().split("T")[0],
  reference: "",
  notes:     "",
};

export default function TransferenciasPage() {
  const table = useApiTable<Transfer>("/transfers");
  const [accounts, setAccounts] = React.useState<CashAccount[]>([]);
  const [open, setOpen] = React.useState(false);
  const [form, setForm] = React.useState<FormState>(EMPTY_FORM);
  const [submitting, setSubmitting] = React.useState(false);
  const [formError, setFormError] = React.useState<string | null>(null);
  const [cancelTarget, setCancelTarget] = React.useState<Transfer | null>(null);
  const [cancelling, setCancelling] = React.useState(false);

  React.useEffect(() => {
    api.get<{ data: CashAccount[] }>("/cash-accounts?per_page=100")
      .then(r => setAccounts((r.data.data ?? []).filter(a => a.status === "active")));
  }, []);

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
      table.refresh();
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
      table.refresh();
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

  const columns: AppColumnDef<Transfer>[] = [
    dateColumn<Transfer>("date", "Fecha"),
    { header: "Origen", cell: ({ row }) => (row.original as any).from_account?.name ?? "-" },
    { header: "Destino", cell: ({ row }) => (row.original as any).to_account?.name ?? "-" },
    { header: "Monto", cell: ({ row }) => <span className="font-medium tabular-nums">{COP(row.original.amount)}</span> },
    { accessorKey: "reference", header: "Referencia" },
    { accessorKey: "notes", header: "Notas" },
    {
      header: "Estado",
      cell: ({ row }) => (
        <Badge variant={badgeVariant(row.original.status)}>
          {row.original.status === "active" ? "Activa" : "Cancelada"}
        </Badge>
      ),
    },
    {
      id: "actions",
      header: "",
      cell: ({ row }) =>
        row.original.status === "active" ? (
          <div className="flex justify-end">
            <Button size="sm" variant="destructive" onClick={() => setCancelTarget(row.original)}>
              Cancelar
            </Button>
          </div>
        ) : null,
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Transferencias</h1>
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

      <DataTable
        columns={columns}
        data={table.data}
        search={table.search}
        onSearchChange={table.setSearch}
        page={table.page}
        onPageChange={table.setPage}
        perPage={table.perPage}
        onPerPageChange={table.setPerPage}
        exportBaseUrl={`${API_BASE}/exports/transfers`}
        loading={table.loading}
        error={table.error}
      />

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
