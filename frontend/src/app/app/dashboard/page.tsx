"use client";

import * as React from "react";
import {
  Activity,
  CalendarDays,
  Clock3,
  FileWarning,
  HeartPulse,
  Plane,
  UserCheck,
  Users,
} from "lucide-react";
import {
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  Legend,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { api } from "@/lib/api";

type DashboardData = {
  metrics: Record<string, number>;
  weekly_attendance: { date: string; status: string; total: number }[];
  recent_activity: { id: number; action: string; module?: string; created_at: string }[];
  pending_requests: { vacations: unknown[]; permissions: unknown[] };
  upcoming_events: { documents: { id: number; name: string; expiration_date: string }[] };
};

const TONE = {
  wine: "#a3175a",
  green: "#0e8f5c",
  amber: "#b9770e",
  red: "#c23b2b",
  indigo: "#4f46e5",
  slate: "#64748b",
  violet: "#7c3aed",
  sky: "#0284c7",
};

const metricMeta: Record<string, { label: string; icon: React.ElementType; tone: string }> = {
  total_employees: { label: "Total empleados", icon: Users, tone: TONE.slate },
  active_employees: { label: "Empleados activos", icon: UserCheck, tone: TONE.green },
  present_today: { label: "Presentes hoy", icon: Activity, tone: TONE.green },
  absent_today: { label: "Ausentes hoy", icon: FileWarning, tone: TONE.red },
  late_today: { label: "Llegadas tarde", icon: Clock3, tone: TONE.amber },
  active_sick_leaves: { label: "Incapacidades activas", icon: HeartPulse, tone: TONE.red },
  pending_requests: { label: "Solicitudes pendientes", icon: CalendarDays, tone: TONE.wine },
  upcoming_vacations: { label: "Vacaciones proximas", icon: Plane, tone: TONE.sky },
};

const attendanceColors: Record<string, string> = {
  present: TONE.green,
  late: TONE.amber,
  absent: TONE.red,
};
const attendanceLabels: Record<string, string> = { present: "Presentes", late: "Tarde", absent: "Ausentes" };

function formatDay(value: string) {
  return new Date(value).toLocaleDateString("es-CO", { day: "2-digit", month: "short" });
}

function daysUntil(value: string) {
  return Math.ceil((new Date(value).getTime() - Date.now()) / 86_400_000);
}

function IconBadge({ tone, children }: { tone: string; children: React.ReactNode }) {
  return (
    <span
      className="grid size-10 shrink-0 place-items-center rounded-lg border shadow-sm"
      style={{ backgroundColor: `${tone}1f`, color: tone, borderColor: `${tone}33` }}
    >
      {children}
    </span>
  );
}

type TipItem = { name?: string; value?: number | string; color?: string; payload?: Record<string, unknown> };

function ChartTooltip({
  active,
  payload,
  label,
  labelFormatter,
}: {
  active?: boolean;
  payload?: TipItem[];
  label?: string | number;
  labelFormatter?: (v: string) => string;
}) {
  if (!active || !payload?.length) return null;
  return (
    <div className="rounded-lg border border-border bg-card px-3 py-2 text-xs shadow-lg">
      {label != null && (
        <p className="mb-1 font-medium">{labelFormatter ? labelFormatter(String(label)) : label}</p>
      )}
      {payload.map((item, i) => (
        <div key={i} className="flex items-center gap-2">
          <span className="size-2 rounded-full" style={{ backgroundColor: item.color }} />
          <span className="text-muted-foreground">{item.name}</span>
          <span className="ml-auto font-semibold tabular-nums">{item.value}</span>
        </div>
      ))}
    </div>
  );
}

type Slice = { name: string; value: number; color: string };

function DonutCard({ title, data }: { title: string; data: Slice[] }) {
  const total = data.reduce((sum, slice) => sum + slice.value, 0);

  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      <CardContent className="h-64">
        {total > 0 ? (
          <div className="relative h-full">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={data}
                  dataKey="value"
                  nameKey="name"
                  innerRadius="58%"
                  outerRadius="82%"
                  paddingAngle={2}
                  stroke="var(--card)"
                  strokeWidth={2}
                >
                  {data.map((slice) => (
                    <Cell key={slice.name} fill={slice.color} />
                  ))}
                </Pie>
                <Tooltip content={<ChartTooltip />} />
                <Legend
                  verticalAlign="bottom"
                  height={28}
                  iconType="circle"
                  formatter={(value) => <span className="text-xs text-muted-foreground">{value}</span>}
                />
              </PieChart>
            </ResponsiveContainer>
            <div className="pointer-events-none absolute inset-x-0 top-[42%] -translate-y-1/2 text-center">
              <p className="text-2xl font-semibold tabular-nums">{total}</p>
              <p className="text-[11px] uppercase tracking-wide text-muted-foreground">Total</p>
            </div>
          </div>
        ) : (
          <div className="flex h-full items-center justify-center text-sm text-muted-foreground">Sin datos aun.</div>
        )}
      </CardContent>
    </Card>
  );
}

function LoadingState() {
  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <div className="h-7 w-40 animate-pulse rounded bg-muted" />
        <div className="h-4 w-80 animate-pulse rounded bg-muted" />
      </div>
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {Array.from({ length: 8 }).map((_, i) => (
          <div key={i} className="h-28 animate-pulse rounded-lg border border-border bg-card" />
        ))}
      </div>
      <div className="grid gap-4 xl:grid-cols-[2fr_1fr]">
        <div className="h-96 animate-pulse rounded-lg border border-border bg-card" />
        <div className="h-96 animate-pulse rounded-lg border border-border bg-card" />
      </div>
    </div>
  );
}

export default function DashboardPage() {
  const [dashboard, setDashboard] = React.useState<DashboardData>();

  React.useEffect(() => {
    api.get<DashboardData>("/dashboard").then((response) => setDashboard(response.data)).catch(() => {});
  }, []);

  const chartData = React.useMemo(() => {
    const grouped = new Map<string, Record<string, string | number>>();
    dashboard?.weekly_attendance.forEach((item) => {
      const row = grouped.get(item.date) ?? { date: item.date };
      row[item.status] = item.total;
      grouped.set(item.date, row);
    });
    return Array.from(grouped.values());
  }, [dashboard]);

  const attendanceTotals = React.useMemo<Slice[]>(() => {
    const totals: Record<string, number> = { present: 0, late: 0, absent: 0 };
    dashboard?.weekly_attendance.forEach((item) => {
      if (item.status in totals) totals[item.status] += item.total;
    });
    return Object.entries(totals).map(([status, value]) => ({
      name: attendanceLabels[status],
      value,
      color: attendanceColors[status],
    }));
  }, [dashboard]);

  const employeeStatus = React.useMemo<Slice[]>(() => {
    const total = dashboard?.metrics.total_employees ?? 0;
    const active = dashboard?.metrics.active_employees ?? 0;
    return [
      { name: "Activos", value: active, color: TONE.green },
      { name: "Inactivos", value: Math.max(total - active, 0), color: TONE.slate },
    ];
  }, [dashboard]);

  const pendingByType = React.useMemo<Slice[]>(
    () => [
      { name: "Vacaciones", value: dashboard?.pending_requests.vacations.length ?? 0, color: TONE.sky },
      { name: "Permisos", value: dashboard?.pending_requests.permissions.length ?? 0, color: TONE.violet },
      { name: "Incapacidades", value: dashboard?.metrics.active_sick_leaves ?? 0, color: TONE.red },
    ],
    [dashboard],
  );

  const expiringDocs = dashboard?.upcoming_events.documents ?? [];

  if (!dashboard) return <LoadingState />;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Dashboard</h1>
        <p className="text-sm text-muted-foreground">Indicadores operativos de Recursos Humanos, en vivo desde la API.</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {Object.entries(metricMeta).map(([key, meta]) => {
          const Icon = meta.icon;
          const value = dashboard.metrics[key];
          return (
            <Card key={key} className="transition-shadow hover:shadow-md">
              <CardContent className="flex items-center gap-4 p-5">
                <IconBadge tone={meta.tone}>
                  <Icon className="size-5" />
                </IconBadge>
                <div className="min-w-0">
                  <p className="truncate text-sm text-muted-foreground">{meta.label}</p>
                  <p className="text-2xl font-semibold tabular-nums">
                    {typeof value === "number" ? value.toLocaleString("es-CO") : "—"}
                  </p>
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>

      <div className="grid gap-4 xl:grid-cols-[2fr_1fr]">
        <Card>
          <CardHeader>
            <CardTitle>Asistencia semanal</CardTitle>
          </CardHeader>
          <CardContent className="h-80">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={chartData} barSize={26}>
                <defs>
                  {Object.entries(attendanceColors).map(([status, color]) => (
                    <linearGradient key={status} id={`bar-${status}`} x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stopColor={color} stopOpacity={1} />
                      <stop offset="100%" stopColor={color} stopOpacity={0.72} />
                    </linearGradient>
                  ))}
                </defs>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="var(--border)" />
                <XAxis
                  dataKey="date"
                  tickFormatter={formatDay}
                  tickLine={false}
                  axisLine={false}
                  tick={{ fontSize: 12, fill: "var(--muted-foreground)" }}
                />
                <YAxis
                  allowDecimals={false}
                  tickLine={false}
                  axisLine={false}
                  width={28}
                  tick={{ fontSize: 12, fill: "var(--muted-foreground)" }}
                />
                <Tooltip
                  cursor={{ fill: "var(--muted)", opacity: 0.5 }}
                  content={<ChartTooltip labelFormatter={formatDay} />}
                />
                <Legend
                  iconType="circle"
                  formatter={(value) => <span className="text-xs text-muted-foreground">{value}</span>}
                />
                <Bar stackId="a" dataKey="present" fill="url(#bar-present)" name="Presentes" />
                <Bar stackId="a" dataKey="late" fill="url(#bar-late)" name="Tarde" />
                <Bar stackId="a" dataKey="absent" fill="url(#bar-absent)" name="Ausentes" radius={[6, 6, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Actividad reciente</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {dashboard.recent_activity?.length ? (
              dashboard.recent_activity.map((activity) => (
                <div
                  key={activity.id}
                  className="flex items-start justify-between gap-3 border-b border-border pb-3 last:border-0 last:pb-0"
                >
                  <div className="flex min-w-0 gap-3">
                    <span className="mt-1.5 size-2 shrink-0 rounded-full bg-primary" />
                    <div className="min-w-0">
                      <p className="truncate text-sm font-medium">{activity.action}</p>
                      <p className="text-xs text-muted-foreground">{activity.module ?? "sistema"}</p>
                    </div>
                  </div>
                  <Badge>{new Date(activity.created_at).toLocaleDateString("es-CO")}</Badge>
                </div>
              ))
            ) : (
              <p className="text-sm text-muted-foreground">Sin actividad registrada.</p>
            )}
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <DonutCard title="Asistencia (7 dias)" data={attendanceTotals} />
        <DonutCard title="Empleados por estado" data={employeeStatus} />
        <DonutCard title="Pendientes por tipo" data={pendingByType} />
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Documentos por vencer (45 dias)</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {expiringDocs.length ? (
            expiringDocs.map((doc) => {
              const left = daysUntil(doc.expiration_date);
              const tone = left <= 7 ? TONE.red : left <= 21 ? TONE.amber : TONE.slate;
              return (
                <div
                  key={doc.id}
                  className="flex items-center justify-between gap-3 border-b border-border pb-3 last:border-0 last:pb-0"
                >
                  <div className="flex min-w-0 items-center gap-3">
                    <IconBadge tone={tone}>
                      <FileWarning className="size-5" />
                    </IconBadge>
                    <div className="min-w-0">
                      <p className="truncate text-sm font-medium">{doc.name}</p>
                      <p className="text-xs text-muted-foreground">
                        Vence {new Date(doc.expiration_date).toLocaleDateString("es-CO")}
                      </p>
                    </div>
                  </div>
                  <Badge style={{ backgroundColor: `${tone}1f`, color: tone }}>
                    {left <= 0 ? "Vencido" : `${left} d`}
                  </Badge>
                </div>
              );
            })
          ) : (
            <p className="text-sm text-muted-foreground">Sin documentos proximos a vencer.</p>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
