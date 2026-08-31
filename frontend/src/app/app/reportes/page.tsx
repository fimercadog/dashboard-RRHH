"use client";

import * as React from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { api } from "@/lib/api";

type Distribution = { name: string; employees: number };

type ReportData = {
  generated_at: string;
  headcount: Record<string, number>;
  attendance_30d: Record<string, number>;
  requests: Record<string, number>;
  documents: Record<string, number>;
  contracts: Record<string, number>;
  by_department: Distribution[];
  by_position: Distribution[];
};

const labels: Record<string, string> = {
  total: "Empleados totales",
  active: "Empleados activos",
  inactive: "Inactivos / retirados",
  hires_month: "Altas este mes",
  terminations_month: "Bajas este mes",
  birthdays_month: "Cumpleanos este mes",
  present: "Asistencias (30d)",
  late: "Tardanzas (30d)",
  absent: "Ausencias (30d)",
  late_minutes: "Minutos de tardanza (30d)",
  vacations_pending: "Vacaciones pendientes",
  permissions_pending: "Permisos pendientes",
  sick_leaves_active: "Incapacidades activas",
  expired: "Documentos vencidos",
  expiring_30d: "Documentos por vencer (30d)",
};

function StatGrid({ data }: { data: Record<string, number> }) {
  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      {Object.entries(data).map(([key, value]) => (
        <Card key={key}>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">{labels[key] ?? key}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-semibold">{value.toLocaleString("es-CO")}</p>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

function DistributionTable({ title, rows }: { title: string; rows: Distribution[] }) {
  const total = rows.reduce((sum, r) => sum + r.employees, 0) || 1;
  return (
    <Card>
      <CardHeader><CardTitle>{title}</CardTitle></CardHeader>
      <CardContent className="space-y-3">
        {rows.length === 0 ? (
          <p className="text-sm text-muted-foreground">Sin datos.</p>
        ) : (
          rows.map((row) => (
            <div key={row.name}>
              <div className="flex justify-between text-sm">
                <span>{row.name}</span>
                <span className="text-muted-foreground">{row.employees}</span>
              </div>
              <div className="mt-1 h-1.5 rounded bg-muted">
                <div className="h-full rounded bg-primary" style={{ width: `${(row.employees / total) * 100}%` }} />
              </div>
            </div>
          ))
        )}
      </CardContent>
    </Card>
  );
}

export default function ReportsPage() {
  const [report, setReport] = React.useState<ReportData | null>(null);

  React.useEffect(() => {
    api.get<ReportData>("/reports").then((res) => setReport(res.data));
  }, []);

  if (!report) {
    return (
      <div className="space-y-6">
        <h1 className="text-2xl font-semibold">Reportes</h1>
        <p className="text-sm text-muted-foreground">Cargando indicadores...</p>
      </div>
    );
  }

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-semibold">Reportes</h1>
        <p className="text-sm text-muted-foreground">
          Indicadores de la operacion. Generado {new Date(report.generated_at).toLocaleString("es-CO")}.
        </p>
      </div>

      <section className="space-y-3">
        <h2 className="text-lg font-semibold">Personal</h2>
        <StatGrid data={report.headcount} />
      </section>

      <section className="space-y-3">
        <h2 className="text-lg font-semibold">Asistencia (ultimos 30 dias)</h2>
        <StatGrid data={report.attendance_30d} />
      </section>

      <section className="space-y-3">
        <h2 className="text-lg font-semibold">Solicitudes y documentos</h2>
        <StatGrid data={{ ...report.requests, ...report.documents }} />
      </section>

      <section className="grid gap-4 lg:grid-cols-3">
        <DistributionTable title="Empleados por area" rows={report.by_department} />
        <DistributionTable title="Empleados por cargo" rows={report.by_position} />
        <DistributionTable
          title="Tipo de contrato"
          rows={Object.entries(report.contracts).map(([name, employees]) => ({ name, employees }))}
        />
      </section>
    </div>
  );
}
