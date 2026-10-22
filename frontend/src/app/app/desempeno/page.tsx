"use client";

import * as React from "react";
import { Plus, Send, Pencil, ClipboardCheck, Clock, Award, Star } from "lucide-react";
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
import { api, API_BASE, PaginatedResponse } from "@/lib/api";
import { AppColumnDef } from "@/lib/table-types";
import { Employee, PerformanceReview } from "@/lib/types";
import { useApiTable } from "@/lib/use-api-table";

const STATUS_LABELS: Record<string, string> = {
  draft:        "Borrador",
  submitted:    "Enviada",
  acknowledged: "Reconocida",
};

const STATUS_VARIANT: Record<string, "muted" | "info" | "warning" | "success" | "danger"> = {
  draft:        "muted",
  submitted:    "info",
  acknowledged: "success",
};

type ApiErrors = Record<string, string[]>;
type PerformanceStats = { total: number; pending: number; submitted: number; avg_rating?: number | null };

// ── Shared employee picker hook ───────────────────────────────────────────────

function useEmployees(load: boolean) {
  const [employees, setEmployees] = React.useState<Employee[]>([]);
  const [loading, setLoading] = React.useState(false);
  React.useEffect(() => {
    if (!load) return;
    async function loadData() {
      setLoading(true);
      try {
        const r = await api.get<PaginatedResponse<Employee>>("/employees", { params: { per_page: 200 } });
        setEmployees(r.data.data ?? []);
      } catch {
        // ignore
      } finally {
        setLoading(false);
      }
    }
    void loadData();
  }, [load]);
  return { employees, loading };
}

// ── Create Review Modal ───────────────────────────────────────────────────────

type CreateReviewModalProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSaved: () => void;
};

const todayStr = () => new Date().toISOString().slice(0, 10);
const endOfMonth = () => {
  const d = new Date();
  return new Date(d.getFullYear(), d.getMonth() + 1, 0).toISOString().slice(0, 10);
};

function CreateReviewModal({ open, onOpenChange, onSaved }: CreateReviewModalProps) {
  const [employeeId, setEmployeeId]   = React.useState("");
  const [periodStart, setPeriodStart] = React.useState(todayStr);
  const [periodEnd, setPeriodEnd]     = React.useState(endOfMonth);
  const [saving, setSaving]           = React.useState(false);
  const [errors, setErrors]           = React.useState<ApiErrors>({});
  const { employees, loading: loadingEmps } = useEmployees(open);

  function resetForm() {
    setEmployeeId(""); setPeriodStart(todayStr()); setPeriodEnd(endOfMonth()); setErrors({});
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
      await api.post("/performance-reviews", {
        employee_id:  Number(employeeId),
        period_start: periodStart,
        period_end:   periodEnd,
      });
      toast.success("Evaluación creada");
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
          <DialogTitle>Nueva evaluación de desempeño</DialogTitle>
        </DialogHeader>
        <form id={formId} onSubmit={submit} className="space-y-4">
          <label className="space-y-1.5 text-sm block">
            <span className="font-medium">Empleado <span className="text-destructive">*</span></span>
            <select
              required
              value={employeeId}
              onChange={(e) => { setEmployeeId(e.target.value); setErrors((p) => ({ ...p, employee_id: [] })); }}
              disabled={loadingEmps}
              className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-primary disabled:opacity-50"
            >
              <option value="">{loadingEmps ? "Cargando..." : "Seleccionar empleado"}</option>
              {employees.map((emp) => (
                <option key={emp.id} value={emp.id}>
                  {emp.first_name} {emp.last_name}
                </option>
              ))}
            </select>
            {errors.employee_id?.[0] && <span className="text-xs text-destructive">{errors.employee_id[0]}</span>}
          </label>

          <div className="grid grid-cols-2 gap-4">
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">Inicio del período <span className="text-destructive">*</span></span>
              <Input type="date" required value={periodStart} onChange={(e) => setPeriodStart(e.target.value)} />
              {errors.period_start?.[0] && <span className="text-xs text-destructive">{errors.period_start[0]}</span>}
            </label>
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">Fin del período <span className="text-destructive">*</span></span>
              <Input type="date" required value={periodEnd} onChange={(e) => setPeriodEnd(e.target.value)} />
              {errors.period_end?.[0] && <span className="text-xs text-destructive">{errors.period_end[0]}</span>}
            </label>
          </div>

          <p className="text-xs text-muted-foreground">
            Las calificaciones se completan editando la evaluación después de crearla.
          </p>
        </form>
        <DialogFooter>
          <Button type="button" variant="outline" onClick={() => handleOpenChange(false)} disabled={saving}>Cancelar</Button>
          <Button type="submit" form={formId} disabled={saving || !employeeId}>{saving ? "Guardando..." : "Crear evaluación"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ── Edit Review Modal ─────────────────────────────────────────────────────────

type EditReviewModalProps = {
  review: PerformanceReview | null;
  onOpenChange: (open: boolean) => void;
  onSaved: () => void;
};

function RatingSelect({ label, value, onChange, error }: {
  label: string;
  value: string;
  onChange: (v: string) => void;
  error?: string;
}) {
  return (
    <label className="space-y-1.5 text-sm">
      <span className="font-medium">{label}</span>
      <select
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-primary"
      >
        <option value="">—</option>
        {[1, 2, 3, 4, 5].map((n) => <option key={n} value={n}>{n}</option>)}
      </select>
      {error && <span className="text-xs text-destructive">{error}</span>}
    </label>
  );
}

function EditReviewModal({ review, onOpenChange, onSaved }: EditReviewModalProps) {
  const [overallRating, setOverallRating]           = React.useState("");
  const [goalsRating, setGoalsRating]               = React.useState("");
  const [competenciesRating, setCompetenciesRating] = React.useState("");
  const [strengths, setStrengths]                   = React.useState("");
  const [improvements, setImprovements]             = React.useState("");
  const [goalsNext, setGoalsNext]                   = React.useState("");
  const [saving, setSaving]                         = React.useState(false);
  const [errors, setErrors]                         = React.useState<ApiErrors>({});

  React.useEffect(() => {
    if (!review) return;
    async function sync() {
      setOverallRating(review!.overall_rating?.toString() ?? "");
      setGoalsRating(review!.goals_rating?.toString() ?? "");
      setCompetenciesRating(review!.competencies_rating?.toString() ?? "");
      setStrengths(review!.strengths ?? "");
      setImprovements(review!.improvements ?? "");
      setGoalsNext(review!.goals_next_period ?? "");
      setErrors({});
    }
    void sync();
  }, [review]);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    if (!review) return;
    setSaving(true);
    setErrors({});
    try {
      await api.put(`/performance-reviews/${review.id}`, {
        overall_rating:       overallRating ? Number(overallRating) : null,
        goals_rating:         goalsRating ? Number(goalsRating) : null,
        competencies_rating:  competenciesRating ? Number(competenciesRating) : null,
        strengths:            strengths || null,
        improvements:         improvements || null,
        goals_next_period:    goalsNext || null,
      });
      toast.success("Evaluación actualizada");
      onSaved();
      onOpenChange(false);
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
  const empName = review?.employee
    ? `${review.employee.first_name} ${review.employee.last_name}`
    : `#${review?.employee_id}`;

  return (
    <Dialog open={!!review} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle>Editar evaluación — {empName}</DialogTitle>
        </DialogHeader>
        <form id={formId} onSubmit={submit} className="space-y-4">
          <div className="grid grid-cols-3 gap-3">
            <RatingSelect label="General" value={overallRating} onChange={setOverallRating} error={errors.overall_rating?.[0]} />
            <RatingSelect label="Objetivos" value={goalsRating} onChange={setGoalsRating} error={errors.goals_rating?.[0]} />
            <RatingSelect label="Competencias" value={competenciesRating} onChange={setCompetenciesRating} error={errors.competencies_rating?.[0]} />
          </div>

          {(["strengths", "improvements", "goals_next_period"] as const).map((field) => {
            const labels: Record<string, string> = {
              strengths: "Fortalezas",
              improvements: "Oportunidades de mejora",
              goals_next_period: "Metas próximo período",
            };
            const vals: Record<string, string> = {
              strengths, improvements, goals_next_period: goalsNext,
            };
            const setters: Record<string, (v: string) => void> = {
              strengths: setStrengths,
              improvements: setImprovements,
              goals_next_period: setGoalsNext,
            };
            return (
              <label key={field} className="space-y-1.5 text-sm block">
                <span className="font-medium">{labels[field]}</span>
                <textarea
                  value={vals[field]}
                  onChange={(e) => setters[field](e.target.value)}
                  rows={2}
                  className="min-h-16 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none placeholder:text-muted-foreground focus-visible:border-primary"
                />
                {errors[field]?.[0] && <span className="text-xs text-destructive">{errors[field][0]}</span>}
              </label>
            );
          })}
        </form>
        <DialogFooter>
          <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={saving}>Cancelar</Button>
          <Button type="submit" form={formId} disabled={saving}>{saving ? "Guardando..." : "Guardar"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ── Submit button ─────────────────────────────────────────────────────────────

function SubmitReviewButton({ review, onDone }: { review: PerformanceReview; onDone: () => void }) {
  const [loading, setLoading] = React.useState(false);
  if (review.status !== "draft") return null;

  async function handleSubmit() {
    setLoading(true);
    try {
      await api.post(`/performance-reviews/${review.id}/submit`);
      toast.success("Evaluación enviada");
      onDone();
    } catch (err) {
      const res = (err as { response?: { data?: { message?: string } } }).response;
      toast.error(res?.data?.message ?? "Error al enviar.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <Button variant="ghost" size="sm" onClick={handleSubmit} disabled={loading} className="text-blue-600 hover:text-blue-800 hover:bg-blue-50">
      <Send className="h-4 w-4" />
      {loading ? "Enviando..." : "Enviar"}
    </Button>
  );
}

// ── Page ─────────────────────────────────────────────────────────────────────

export default function DesempenoPage() {
  const table = useApiTable<PerformanceReview>("/performance-reviews");
  const [createOpen, setCreateOpen]       = React.useState(false);
  const [editReview, setEditReview]       = React.useState<PerformanceReview | null>(null);
  const [perfStats, setPerfStats]         = React.useState<PerformanceStats | null>(null);
  const [perfStatsStatus, setPerfStatsStatus] = React.useState<"loading" | "error" | "ready">("loading");

  React.useEffect(() => {
    api.get<PerformanceStats>("/performance-reviews/stats")
      .then((r) => { setPerfStats(r.data); setPerfStatsStatus("ready"); })
      .catch(() => setPerfStatsStatus("error"));
  }, []);

  const columns: AppColumnDef<PerformanceReview>[] = [
    {
      header: "Empleado",
      cell: ({ row }) => {
        const e = row.original.employee;
        return e ? `${e.first_name} ${e.last_name}` : `#${row.original.employee_id}`;
      },
    },
    {
      header: "Período",
      cell: ({ row }) => `${row.original.period_start} — ${row.original.period_end}`,
    },
    {
      header: "Calificación",
      cell: ({ row }) => {
        const r = row.original.overall_rating;
        return r != null ? (
          <span className="font-medium">{r} / 5</span>
        ) : (
          <span className="text-muted-foreground">—</span>
        );
      },
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
      id: "actions",
      header: "",
      cell: ({ row }) => (
        <div className="flex justify-end gap-1">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => setEditReview(row.original)}
            className="text-muted-foreground hover:text-foreground"
          >
            <Pencil className="h-4 w-4" />
          </Button>
          <SubmitReviewButton review={row.original} onDone={table.refresh} />
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Evaluaciones de Desempeño</h1>
          <p className="text-sm text-muted-foreground">Registro y seguimiento de evaluaciones periódicas.</p>
        </div>
        <Button onClick={() => setCreateOpen(true)}>
          <Plus className="h-4 w-4" /> Nueva evaluación
        </Button>
      </div>

      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <KpiCard label="Total evaluaciones" value={perfStats?.total ?? null} icon={ClipboardCheck} status={perfStatsStatus} />
        <KpiCard label="Pendientes" value={perfStats?.pending ?? null} icon={Clock} status={perfStatsStatus} />
        <KpiCard label="Completadas" value={perfStats?.submitted ?? null} icon={Award} status={perfStatsStatus} />
        <KpiCard label="Calif. promedio" value={perfStats?.avg_rating != null ? Number(perfStats.avg_rating).toFixed(1) : null} icon={Star} status={perfStatsStatus} unit="/ 5" />
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
        exportBaseUrl={`${API_BASE}/exports/performance-reviews`}
        loading={table.loading}
        error={table.error}
      />

      <CreateReviewModal open={createOpen} onOpenChange={setCreateOpen} onSaved={table.refresh} />
      <EditReviewModal review={editReview} onOpenChange={(open) => { if (!open) setEditReview(null); }} onSaved={table.refresh} />
    </div>
  );
}
