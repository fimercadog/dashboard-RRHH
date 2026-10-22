"use client";

import * as React from "react";
import { Plus, XCircle, AlertTriangle, AlertCircle, ShieldCheck, Calendar } from "lucide-react";
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
import { SstIncident, Employee } from "@/lib/types";
import { PaginatedResponse } from "@/lib/api";
import { useApiTable } from "@/lib/use-api-table";

const TYPE_LABELS: Record<string, string> = {
  near_miss:             "Casi accidente",
  incident:              "Incidente",
  accident:              "Accidente",
  occupational_disease:  "Enfermedad laboral",
};

const SEVERITY_LABELS: Record<string, string> = {
  low:      "Baja",
  medium:   "Media",
  high:     "Alta",
  critical: "Crítica",
};

const SEVERITY_VARIANT: Record<string, "muted" | "info" | "warning" | "success" | "danger"> = {
  low:      "muted",
  medium:   "info",
  high:     "warning",
  critical: "danger",
};

const STATUS_LABELS: Record<string, string> = {
  reported:     "Reportado",
  investigating:"En investigación",
  resolved:     "Resuelto",
  closed:       "Cerrado",
};

const STATUS_VARIANT: Record<string, "muted" | "info" | "warning" | "success" | "danger"> = {
  reported:     "muted",
  investigating:"warning",
  resolved:     "info",
  closed:       "success",
};

type ApiErrors = Record<string, string[]>;
type SstStats = { total: number; open: number; closed: number; this_month: number };

// ── Create Incident Modal ─────────────────────────────────────────────────────

type CreateIncidentModalProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSaved: () => void;
};

const todayStr = () => new Date().toISOString().slice(0, 10);

function CreateIncidentModal({ open, onOpenChange, onSaved }: CreateIncidentModalProps) {
  const [employeeId, setEmployeeId]     = React.useState<number | "">("");
  const [employees, setEmployees]       = React.useState<Pick<Employee, "id" | "first_name" | "last_name">[]>([]);
  const [incidentDate, setIncidentDate] = React.useState(todayStr);
  const [type, setType]                 = React.useState("incident");
  const [severity, setSeverity]         = React.useState("low");
  const [location, setLocation]         = React.useState("");
  const [description, setDescription]   = React.useState("");
  const [saving, setSaving]             = React.useState(false);
  const [errors, setErrors]             = React.useState<ApiErrors>({});

  React.useEffect(() => {
    if (!open || employees.length > 0) return;
    api.get<PaginatedResponse<Employee>>("/employees", { params: { per_page: 200 } })
      .then((r) => setEmployees(r.data.data))
      .catch(() => {/* non-fatal */});
  }, [open, employees.length]);

  function resetForm() {
    setEmployeeId(""); setIncidentDate(todayStr()); setType("incident");
    setSeverity("low"); setLocation(""); setDescription(""); setErrors({});
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
      await api.post("/sst/incidents", {
        employee_id:   employeeId || null,
        incident_date: incidentDate,
        type,
        severity,
        location:    location || null,
        description,
      });
      toast.success("Incidente registrado");
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
          <DialogTitle>Nuevo incidente SST</DialogTitle>
        </DialogHeader>
        <form id={formId} onSubmit={submit} className="space-y-4">
          <label className="space-y-1.5 text-sm block">
            <span className="font-medium">Empleado <span className="text-muted-foreground">(opcional)</span></span>
            <select
              value={employeeId}
              onChange={(e) => setEmployeeId(e.target.value ? Number(e.target.value) : "")}
              className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-primary"
            >
              <option value="">Sin empleado asociado</option>
              {employees.map((emp) => (
                <option key={emp.id} value={emp.id}>{emp.first_name} {emp.last_name}</option>
              ))}
            </select>
            {errors.employee_id?.[0] && <span className="text-xs text-destructive">{errors.employee_id[0]}</span>}
          </label>

          <label className="space-y-1.5 text-sm block">
            <span className="font-medium">Fecha del incidente <span className="text-destructive">*</span></span>
            <Input type="date" required value={incidentDate} onChange={(e) => setIncidentDate(e.target.value)} />
            {errors.incident_date?.[0] && <span className="text-xs text-destructive">{errors.incident_date[0]}</span>}
          </label>

          <div className="grid grid-cols-2 gap-4">
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">Tipo <span className="text-destructive">*</span></span>
              <select
                value={type}
                onChange={(e) => setType(e.target.value)}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-primary"
              >
                <option value="near_miss">Casi accidente</option>
                <option value="incident">Incidente</option>
                <option value="accident">Accidente</option>
                <option value="occupational_disease">Enfermedad laboral</option>
              </select>
            </label>

            <label className="space-y-1.5 text-sm">
              <span className="font-medium">Severidad <span className="text-destructive">*</span></span>
              <select
                value={severity}
                onChange={(e) => setSeverity(e.target.value)}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-primary"
              >
                <option value="low">Baja</option>
                <option value="medium">Media</option>
                <option value="high">Alta</option>
                <option value="critical">Crítica</option>
              </select>
            </label>
          </div>

          <label className="space-y-1.5 text-sm block">
            <span className="font-medium">Ubicación</span>
            <Input value={location} onChange={(e) => setLocation(e.target.value)} placeholder="Ej: Planta 2, Bodega A" />
          </label>

          <label className="space-y-1.5 text-sm block">
            <span className="font-medium">Descripción <span className="text-destructive">*</span></span>
            <textarea
              required
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              rows={3}
              className="min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none placeholder:text-muted-foreground focus-visible:border-primary"
              placeholder="Describe el incidente..."
            />
            {errors.description?.[0] && <span className="text-xs text-destructive">{errors.description[0]}</span>}
          </label>
        </form>
        <DialogFooter>
          <Button type="button" variant="outline" onClick={() => handleOpenChange(false)} disabled={saving}>Cancelar</Button>
          <Button type="submit" form={formId} disabled={saving}>{saving ? "Guardando..." : "Registrar"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ── Close button ─────────────────────────────────────────────────────────────

function CloseIncidentButton({ incident, onDone }: { incident: SstIncident; onDone: () => void }) {
  const [closing, setClosing] = React.useState(false);
  if (incident.status === "closed") return null;
  async function doClose() {
    setClosing(true);
    try {
      await api.post(`/sst/incidents/${incident.id}/close`);
      onDone();
    } catch {
      toast.error("No se pudo cerrar el incidente.");
    } finally {
      setClosing(false);
    }
  }
  return (
    <Button variant="ghost" size="sm" onClick={doClose} disabled={closing} className="text-muted-foreground hover:text-foreground">
      <XCircle className="h-4 w-4" />
      {closing ? "Cerrando..." : "Cerrar"}
    </Button>
  );
}

// ── Page ─────────────────────────────────────────────────────────────────────

export default function SstPage() {
  const table = useApiTable<SstIncident>("/sst/incidents");
  const [modalOpen, setModalOpen] = React.useState(false);
  const [sstStats, setSstStats] = React.useState<SstStats | null>(null);
  const [sstStatsStatus, setSstStatsStatus] = React.useState<"loading" | "error" | "ready">("loading");

  React.useEffect(() => {
    api.get<SstStats>("/sst/incidents/stats")
      .then((r) => { setSstStats(r.data); setSstStatsStatus("ready"); })
      .catch(() => setSstStatsStatus("error"));
  }, []);

  const columns: AppColumnDef<SstIncident>[] = [
    dateColumn<SstIncident>("incident_date", "Fecha"),
    {
      header: "Empleado",
      cell: ({ row }) => {
        const e = row.original.employee;
        return e ? `${e.first_name} ${e.last_name}` : <span className="text-muted-foreground">—</span>;
      },
    },
    {
      header: "Tipo",
      cell: ({ row }) => TYPE_LABELS[row.original.type] ?? row.original.type,
    },
    {
      header: "Severidad",
      cell: ({ row }) => (
        <Badge variant={SEVERITY_VARIANT[row.original.severity] ?? badgeVariant(row.original.severity)}>
          {SEVERITY_LABELS[row.original.severity] ?? row.original.severity}
        </Badge>
      ),
    },
    {
      header: "Estado",
      cell: ({ row }) => (
        <Badge variant={STATUS_VARIANT[row.original.status] ?? badgeVariant(row.original.status)}>
          {STATUS_LABELS[row.original.status] ?? row.original.status}
        </Badge>
      ),
    },
    {
      header: "Días perdidos",
      cell: ({ row }) => row.original.days_lost ?? 0,
    },
    {
      id: "actions",
      header: "",
      cell: ({ row }) => (
        <div className="flex justify-end">
          <CloseIncidentButton incident={row.original} onDone={table.refresh} />
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Seguridad y Salud en el Trabajo</h1>
          <p className="text-sm text-muted-foreground">Registro y seguimiento de incidentes laborales.</p>
        </div>
        <Button onClick={() => setModalOpen(true)}>
          <Plus className="h-4 w-4" /> Nuevo incidente
        </Button>
      </div>

      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <KpiCard label="Total incidentes" value={sstStats?.total ?? null} icon={AlertTriangle} status={sstStatsStatus} />
        <KpiCard label="Abiertos" value={sstStats?.open ?? null} icon={AlertCircle} status={sstStatsStatus} />
        <KpiCard label="Cerrados" value={sstStats?.closed ?? null} icon={ShieldCheck} status={sstStatsStatus} />
        <KpiCard label="Este mes" value={sstStats?.this_month ?? null} icon={Calendar} status={sstStatsStatus} />
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
        exportBaseUrl={`${API_BASE}/exports/sst-incidents`}
        loading={table.loading}
        error={table.error}
      />

      <CreateIncidentModal open={modalOpen} onOpenChange={setModalOpen} onSaved={table.refresh} />
    </div>
  );
}
