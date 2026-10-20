"use client";

import { useCallback, useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { ArrowLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { api } from "@/lib/api";
import { AttendanceStats, AttendanceStatsByEmployee } from "@/lib/types";

type Filters = { from: string; to: string; department_id: string; employee_id: string };

const today = new Date().toISOString().slice(0, 10);
const firstOfMonth = today.slice(0, 8) + "01";

const DEFAULT_FILTERS: Filters = { from: firstOfMonth, to: today, department_id: "", employee_id: "" };

function KpiCard({ label, value, sub }: { label: string; value: string | number; sub?: string }) {
  return (
    <div className="rounded-lg border bg-card p-4 space-y-1">
      <p className="text-sm text-muted-foreground">{label}</p>
      <p className="text-2xl font-bold">{value}</p>
      {sub && <p className="text-xs text-muted-foreground">{sub}</p>}
    </div>
  );
}

function EmployeeTable({ rows }: { rows: AttendanceStatsByEmployee[] }) {
  if (!rows.length) return <p className="text-sm text-muted-foreground py-4">Sin registros en el período seleccionado.</p>;
  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b text-left text-muted-foreground">
            <th className="py-2 pr-4 font-medium">Empleado</th>
            <th className="py-2 pr-4 font-medium">Área</th>
            <th className="py-2 pr-4 font-medium text-right">Aus. justif.</th>
            <th className="py-2 pr-4 font-medium text-right">Aus. no justif.</th>
            <th className="py-2 pr-4 font-medium text-right">Tardanzas</th>
            <th className="py-2 font-medium text-right">Min. retraso</th>
          </tr>
        </thead>
        <tbody>
          {rows.map((r) => (
            <tr key={r.employee_id} className="border-b last:border-0 hover:bg-muted/30">
              <td className="py-2 pr-4">{r.full_name}</td>
              <td className="py-2 pr-4 text-muted-foreground">{r.department}</td>
              <td className="py-2 pr-4 text-right">{r.absences_justified}</td>
              <td className="py-2 pr-4 text-right">{r.absences_unjustified}</td>
              <td className="py-2 pr-4 text-right">{r.tardiness_count}</td>
              <td className="py-2 text-right">{r.tardiness_minutes}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

export default function AttendanceStatsPage() {
  const router = useRouter();
  const [filters, setFilters] = useState<Filters>(DEFAULT_FILTERS);
  const [applied, setApplied] = useState<Filters>(DEFAULT_FILTERS);
  const [data, setData] = useState<AttendanceStats | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback((f: Filters) => {
    setLoading(true);
    setError(null);
    const params = new URLSearchParams({ from: f.from, to: f.to });
    if (f.department_id) params.set("department_id", f.department_id);
    if (f.employee_id) params.set("employee_id", f.employee_id);

    api
      .get<AttendanceStats>(`/attendance-stats?${params}`)
      .then((res) => setData(res.data))
      .catch(() => setError("No se pudieron cargar los indicadores."))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => { load(applied); }, [load, applied]);

  function apply() {
    setApplied({ ...filters });
  }

  const s = data?.summary;

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => router.push('/app/asistencia')}>
          <ArrowLeft className="h-4 w-4 mr-2" /> Asistencia
        </Button>
      </div>

      <div>
        <h1 className="text-2xl font-bold">Indicadores de asistencia</h1>
        <p className="text-sm text-muted-foreground mt-1">
          Solo se contabilizan registros explícitos. Justificadas: permiso, vacaciones, incapacidad.
        </p>
      </div>

      {/* Filtros */}
      <div className="flex flex-wrap gap-3 items-end">
        <div className="flex flex-col gap-1">
          <label className="text-xs text-muted-foreground">Desde</label>
          <input
            type="date"
            value={filters.from}
            onChange={(e) => setFilters((f) => ({ ...f, from: e.target.value }))}
            className="h-9 rounded-md border bg-background px-3 text-sm"
          />
        </div>
        <div className="flex flex-col gap-1">
          <label className="text-xs text-muted-foreground">Hasta</label>
          <input
            type="date"
            value={filters.to}
            onChange={(e) => setFilters((f) => ({ ...f, to: e.target.value }))}
            className="h-9 rounded-md border bg-background px-3 text-sm"
          />
        </div>
        <div className="flex flex-col gap-1">
          <label className="text-xs text-muted-foreground">Área (ID)</label>
          <input
            type="number"
            placeholder="Todas"
            value={filters.department_id}
            onChange={(e) => setFilters((f) => ({ ...f, department_id: e.target.value }))}
            className="h-9 w-28 rounded-md border bg-background px-3 text-sm"
          />
        </div>
        <div className="flex flex-col gap-1">
          <label className="text-xs text-muted-foreground">Empleado (ID)</label>
          <input
            type="number"
            placeholder="Todos"
            value={filters.employee_id}
            onChange={(e) => setFilters((f) => ({ ...f, employee_id: e.target.value }))}
            className="h-9 w-28 rounded-md border bg-background px-3 text-sm"
          />
        </div>
        <Button onClick={apply} disabled={loading} size="sm">
          {loading ? "Cargando…" : "Aplicar"}
        </Button>
      </div>

      {error && <p className="text-destructive text-sm">{error}</p>}

      {/* KPI cards */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Ausencias no justificadas" value={s?.absences.unjustified ?? "—"} />
        <KpiCard label="Ausencias justificadas" value={s?.absences.justified ?? "—"} sub="Permiso / vacaciones / incapacidad" />
        <KpiCard label="Llegadas tarde" value={s?.tardiness.count ?? "—"} />
        <KpiCard label="Minutos de retraso" value={s?.tardiness.total_minutes ?? "—"} sub="Total acumulado en el período" />
      </div>

      {/* Tabla por empleado */}
      {data && (
        <div className="rounded-lg border bg-card p-4 space-y-3">
          <h2 className="font-semibold text-base">Desglose por empleado</h2>
          <EmployeeTable rows={data.by_employee} />
        </div>
      )}

      {/* Tabla por departamento */}
      {data && data.by_department.length > 0 && (
        <div className="rounded-lg border bg-card p-4 space-y-3">
          <h2 className="font-semibold text-base">Resumen por área</h2>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left text-muted-foreground">
                  <th className="py-2 pr-4 font-medium">Área</th>
                  <th className="py-2 pr-4 font-medium text-right">Aus. justif.</th>
                  <th className="py-2 pr-4 font-medium text-right">Aus. no justif.</th>
                  <th className="py-2 pr-4 font-medium text-right">Tardanzas</th>
                  <th className="py-2 font-medium text-right">Min. retraso</th>
                </tr>
              </thead>
              <tbody>
                {data.by_department.map((d) => (
                  <tr key={d.department} className="border-b last:border-0 hover:bg-muted/30">
                    <td className="py-2 pr-4">{d.department}</td>
                    <td className="py-2 pr-4 text-right">{d.absences_justified}</td>
                    <td className="py-2 pr-4 text-right">{d.absences_unjustified}</td>
                    <td className="py-2 pr-4 text-right">{d.tardiness_count}</td>
                    <td className="py-2 text-right">{d.tardiness_minutes}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}
