"use client";

import * as React from "react";
import { Archive, CheckCircle, FileEdit, Play, Plus, Eye, Receipt } from "lucide-react";
import { KpiCard } from "@/components/kpi-card";
import { toast } from "sonner";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { DataTable } from "@/components/data-table/data-table";
import { api, API_BASE } from "@/lib/api";
import { AppColumnDef, dateColumn } from "@/lib/table-types";
import { PayrollPeriod, PayrollRun } from "@/lib/types";
import { useApiTable } from "@/lib/use-api-table";

const COP = (v: number) =>
  new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(v);

const PERIOD_TYPE_LABELS: Record<string, string> = {
  monthly: "Mensual",
  biweekly: "Quincenal",
  weekly: "Semanal",
};

const STATUS_LABELS: Record<string, string> = {
  draft: "Borrador",
  running: "Procesando",
  approved: "Aprobada",
  closed: "Cerrada",
};

const STATUS_VARIANT: Record<string, "muted" | "info" | "warning" | "success" | "danger"> = {
  draft: "muted",
  running: "warning",
  approved: "info",
  closed: "success",
};

type ApiErrors = Record<string, string[]>;

// ── Create Period Modal ───────────────────────────────────────────────────────

type CreatePeriodModalProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSaved: () => void;
};

const todayStr = () => new Date().toISOString().slice(0, 10);
const endOfMonth = () => {
  const d = new Date();
  return new Date(d.getFullYear(), d.getMonth() + 1, 0).toISOString().slice(0, 10);
};

function CreatePeriodModal({ open, onOpenChange, onSaved }: CreatePeriodModalProps) {
  const [name, setName] = React.useState("");
  const [periodType, setPeriodType] = React.useState<string>("monthly");
  const [startDate, setStartDate] = React.useState(todayStr);
  const [endDate, setEndDate] = React.useState(endOfMonth);
  const [paymentDate, setPaymentDate] = React.useState(endOfMonth);
  const [notes, setNotes] = React.useState("");
  const [saving, setSaving] = React.useState(false);
  const [errors, setErrors] = React.useState<ApiErrors>({});

  function resetForm() {
    setName(""); setPeriodType("monthly");
    setStartDate(todayStr()); setEndDate(endOfMonth()); setPaymentDate(endOfMonth());
    setNotes(""); setErrors({});
  }

  function handleOpenChange(next: boolean) {
    if (!next) resetForm();
    onOpenChange(next);
  }

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setErrors({});
    try {
      await api.post("/payroll/periods", {
        name, period_type: periodType, start_date: startDate,
        end_date: endDate, payment_date: paymentDate, notes: notes || null,
      });
      toast.success("Período de nómina creado");
      onSaved();
      handleOpenChange(false);
    } catch (err) {
      const res = (err as { response?: { status?: number; data?: { errors?: ApiErrors; message?: string } } }).response;
      if (res?.status === 422 && res.data?.errors) {
        setErrors(res.data.errors);
        toast.error("Hay campos por corregir.");
      } else {
        toast.error(res?.data?.message ?? "Error al guardar.");
      }
    } finally {
      setSaving(false);
    }
  }

  const formId = React.useId();

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle>Nuevo período de nómina</DialogTitle>
        </DialogHeader>
        <form id={formId} onSubmit={submit} className="space-y-4">
          <label className="space-y-1.5 text-sm block">
            <span className="font-medium">Nombre <span className="text-destructive">*</span></span>
            <Input required value={name} onChange={(e) => setName(e.target.value)} placeholder="Ej: Nómina enero 2025" />
            {errors.name?.[0] && <span className="text-xs text-destructive">{errors.name[0]}</span>}
          </label>

          <label className="space-y-1.5 text-sm block">
            <span className="font-medium">Tipo de período</span>
            <select
              value={periodType}
              onChange={(e) => setPeriodType(e.target.value)}
              className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-primary"
            >
              <option value="monthly">Mensual</option>
              <option value="biweekly">Quincenal</option>
              <option value="weekly">Semanal</option>
            </select>
          </label>

          <div className="grid grid-cols-2 gap-4">
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">Fecha inicio <span className="text-destructive">*</span></span>
              <Input type="date" required value={startDate} onChange={(e) => setStartDate(e.target.value)} />
            </label>
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">Fecha fin <span className="text-destructive">*</span></span>
              <Input type="date" required value={endDate} onChange={(e) => setEndDate(e.target.value)} />
            </label>
            <label className="space-y-1.5 text-sm col-span-2">
              <span className="font-medium">Fecha de pago <span className="text-destructive">*</span></span>
              <Input type="date" required value={paymentDate} onChange={(e) => setPaymentDate(e.target.value)} />
            </label>
          </div>

          <label className="space-y-1.5 text-sm block">
            <span className="font-medium">Notas</span>
            <textarea
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              rows={2}
              className="min-h-16 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none placeholder:text-muted-foreground focus-visible:border-primary"
            />
          </label>
        </form>
        <DialogFooter>
          <Button type="button" variant="outline" onClick={() => handleOpenChange(false)} disabled={saving}>Cancelar</Button>
          <Button type="submit" form={formId} disabled={saving}>{saving ? "Guardando..." : "Crear período"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ── Run Result Modal ──────────────────────────────────────────────────────────

function RunResultModal({ run, onClose }: { run: PayrollRun; onClose: () => void }) {
  return (
    <Dialog open onOpenChange={(v) => !v && onClose()}>
      <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Resultado — Liquidación #{run.run_number}</DialogTitle>
        </DialogHeader>
        <div className="space-y-4">
          <div className="grid grid-cols-2 gap-3 text-sm">
            <div className="rounded-lg border p-3 space-y-1">
              <p className="text-muted-foreground text-xs">Total devengado</p>
              <p className="font-semibold">{COP(run.total_devengado)}</p>
            </div>
            <div className="rounded-lg border p-3 space-y-1">
              <p className="text-muted-foreground text-xs">Total deducciones</p>
              <p className="font-semibold">{COP(run.total_deducciones)}</p>
            </div>
            <div className="rounded-lg border p-3 space-y-1">
              <p className="text-muted-foreground text-xs">Total neto</p>
              <p className="font-semibold text-green-700">{COP(run.total_neto)}</p>
            </div>
            <div className="rounded-lg border p-3 space-y-1">
              <p className="text-muted-foreground text-xs">Aportes empresa</p>
              <p className="font-semibold">{COP(run.total_aportes_empresa)}</p>
            </div>
          </div>
          <p className="text-sm text-muted-foreground">{run.total_employees} empleados procesados</p>

          {run.items && run.items.length > 0 && (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left text-muted-foreground text-xs">
                    <th className="py-2 pr-3 font-medium">Empleado</th>
                    <th className="py-2 pr-3 font-medium text-right">Devengado</th>
                    <th className="py-2 pr-3 font-medium text-right">Deducciones</th>
                    <th className="py-2 font-medium text-right">Neto</th>
                  </tr>
                </thead>
                <tbody>
                  {run.items.map((item) => (
                    <tr key={item.id} className="border-b last:border-0 hover:bg-muted/30">
                      <td className="py-2 pr-3">{item.employee?.full_name ?? `#${item.employee_id}`}</td>
                      <td className="py-2 pr-3 text-right tabular-nums">{COP(item.total_devengado)}</td>
                      <td className="py-2 pr-3 text-right tabular-nums">{COP(item.total_deducciones)}</td>
                      <td className="py-2 text-right tabular-nums font-medium">{COP(item.total_neto)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={onClose}>Cerrar</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ── Period Actions ────────────────────────────────────────────────────────────

function PeriodActions({ period, onDone }: { period: PayrollPeriod; onDone: () => void }) {
  const [running, setRunning] = React.useState(false);
  const [approving, setApproving] = React.useState(false);
  const [closing, setClosing] = React.useState(false);
  const [currentRun, setCurrentRun] = React.useState<PayrollRun | null>(null);

  async function runPayroll() {
    setRunning(true);
    try {
      const res = await api.post<PayrollRun>(`/payroll/periods/${period.id}/run`);
      toast.success("Nómina calculada correctamente");
      setCurrentRun(res.data);
      onDone();
    } catch (err) {
      const res = (err as { response?: { data?: { message?: string } } }).response;
      toast.error(res?.data?.message ?? "Error al calcular la nómina.");
    } finally {
      setRunning(false);
    }
  }

  async function approvePeriod() {
    setApproving(true);
    try {
      const runRes = await api.get<PayrollRun>(`/payroll/runs/last?period_id=${period.id}`);
      const run = runRes.data;
      if (!run?.id) { toast.error("No hay liquidación para aprobar."); return; }
      await api.post(`/payroll/runs/${run.id}/approve`);
      toast.success("Liquidación aprobada");
      onDone();
    } catch (err) {
      const res = (err as { response?: { data?: { message?: string } } }).response;
      toast.error(res?.data?.message ?? "Error al aprobar.");
    } finally {
      setApproving(false);
    }
  }

  async function closePeriod() {
    setClosing(true);
    try {
      await api.post(`/payroll/periods/${period.id}/close`);
      toast.success("Período cerrado");
      onDone();
    } catch (err) {
      const res = (err as { response?: { data?: { message?: string } } }).response;
      toast.error(res?.data?.message ?? "Error al cerrar el período.");
    } finally {
      setClosing(false);
    }
  }

  return (
    <>
      <div className="flex justify-end gap-1">
        {period.status === "draft" && (
          <Button variant="ghost" size="sm" onClick={runPayroll} disabled={running}
            className="text-blue-700 hover:text-blue-900 hover:bg-blue-50">
            <Play className="h-4 w-4" />
            {running ? "Calculando..." : "Calcular"}
          </Button>
        )}
        {period.status === "running" && (
          <>
            <Button variant="ghost" size="sm" onClick={() => {
              api.get<PayrollRun>(`/payroll/runs/last?period_id=${period.id}`).then((r) => setCurrentRun(r.data)).catch(() => {});
            }} className="text-slate-600">
              <Eye className="h-4 w-4" /> Ver resultado
            </Button>
            <Button variant="ghost" size="sm" onClick={approvePeriod} disabled={approving}
              className="text-green-700 hover:text-green-900 hover:bg-green-50">
              <CheckCircle className="h-4 w-4" />
              {approving ? "Aprobando..." : "Aprobar"}
            </Button>
          </>
        )}
        {period.status === "approved" && (
          <Button variant="ghost" size="sm" onClick={closePeriod} disabled={closing}
            className="text-orange-700 hover:text-orange-900 hover:bg-orange-50">
            {closing ? "Cerrando..." : "Cerrar período"}
          </Button>
        )}
      </div>
      {currentRun && <RunResultModal run={currentRun} onClose={() => setCurrentRun(null)} />}
    </>
  );
}

// ── Page ─────────────────────────────────────────────────────────────────────

const columns: AppColumnDef<PayrollPeriod>[] = [
  { accessorKey: "name", header: "Período" },
  {
    header: "Tipo",
    cell: ({ row }) => PERIOD_TYPE_LABELS[row.original.period_type] ?? row.original.period_type,
  },
  dateColumn<PayrollPeriod>("start_date", "Inicio"),
  dateColumn<PayrollPeriod>("end_date", "Fin"),
  dateColumn<PayrollPeriod>("payment_date", "Pago"),
  {
    header: "Estado",
    cell: ({ row }) => (
      <Badge variant={STATUS_VARIANT[row.original.status] ?? badgeVariant(row.original.status)}>
        {STATUS_LABELS[row.original.status] ?? row.original.status}
      </Badge>
    ),
  },
];

type PayrollStats = { total: number; draft: number; running: number; approved: number; closed: number };

export default function NominaPage() {
  const table = useApiTable<PayrollPeriod>("/payroll/periods");
  const [modalOpen, setModalOpen] = React.useState(false);
  const [stats, setStats] = React.useState<PayrollStats | null>(null);
  const [statsStatus, setStatsStatus] = React.useState<"loading" | "error" | "ready">("loading");

  React.useEffect(() => {
    api.get<PayrollStats>("/payroll/periods/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const tableColumns: AppColumnDef<PayrollPeriod>[] = [
    ...columns,
    {
      id: "actions",
      header: "",
      cell: ({ row }) => (
        <PeriodActions period={row.original} onDone={table.refresh} />
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Nómina y liquidaciones</h1>
          <p className="text-sm text-muted-foreground">Períodos de nómina, liquidación y cierre.</p>
        </div>
        <Button onClick={() => setModalOpen(true)}>
          <Plus className="h-4 w-4" /> Nuevo período
        </Button>
      </div>

      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <KpiCard label="Total períodos" value={stats?.total ?? null} icon={Receipt} status={statsStatus} />
        <KpiCard label="En borrador" value={stats?.draft ?? null} icon={FileEdit} status={statsStatus} />
        <KpiCard label="Aprobados" value={stats?.approved ?? null} icon={CheckCircle} status={statsStatus} />
        <KpiCard label="Cerrados" value={stats?.closed ?? null} icon={Archive} status={statsStatus} />
      </div>

      <DataTable
        columns={tableColumns}
        data={table.data}
        search={table.search}
        onSearchChange={table.setSearch}
        page={table.page}
        onPageChange={table.setPage}
        perPage={table.perPage}
        onPerPageChange={table.setPerPage}
        exportBaseUrl={`${API_BASE}/exports/payroll-periods`}
        loading={table.loading}
        error={table.error}
      />

      <CreatePeriodModal open={modalOpen} onOpenChange={setModalOpen} onSaved={table.refresh} />
    </div>
  );
}
