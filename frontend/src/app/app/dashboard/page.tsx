"use client";

import * as React from "react";
import { Activity, CalendarDays, Clock3, FileWarning, Users } from "lucide-react";
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from "recharts";
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

const metricLabels: Record<string, { label: string; icon: React.ElementType }> = {
  total_employees: { label: "Total empleados", icon: Users },
  active_employees: { label: "Empleados activos", icon: Users },
  present_today: { label: "Presentes hoy", icon: Activity },
  absent_today: { label: "Ausentes hoy", icon: FileWarning },
  late_today: { label: "Llegadas tarde", icon: Clock3 },
  active_sick_leaves: { label: "Incapacidades activas", icon: FileWarning },
  pending_requests: { label: "Solicitudes pendientes", icon: CalendarDays },
  upcoming_vacations: { label: "Vacaciones proximas", icon: CalendarDays },
};

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

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold">Dashboard</h1>
        <p className="text-sm text-muted-foreground">Indicadores operativos conectados a SQLite via API Laravel.</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {Object.entries(metricLabels).map(([key, meta]) => {
          const Icon = meta.icon;
          return (
            <Card key={key}>
              <CardHeader className="flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">{meta.label}</CardTitle>
                <Icon className="h-4 w-4 text-primary" />
              </CardHeader>
              <CardContent>
                <p className="text-2xl font-semibold">{dashboard?.metrics[key] ?? "..."}</p>
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
              <BarChart data={chartData}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                <XAxis dataKey="date" />
                <YAxis allowDecimals={false} />
                <Tooltip />
                <Bar dataKey="present" fill="#0f766e" name="Presentes" />
                <Bar dataKey="late" fill="#ca8a04" name="Tarde" />
                <Bar dataKey="absent" fill="#b42318" name="Ausentes" />
              </BarChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>Actividad reciente</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {dashboard?.recent_activity?.length ? dashboard.recent_activity.map((activity) => (
              <div key={activity.id} className="flex items-center justify-between gap-3 border-b border-border pb-3 last:border-0">
                <div>
                  <p className="text-sm font-medium">{activity.action}</p>
                  <p className="text-xs text-muted-foreground">{activity.module ?? "sistema"}</p>
                </div>
                <Badge>{new Date(activity.created_at).toLocaleDateString("es-CO")}</Badge>
              </div>
            )) : <p className="text-sm text-muted-foreground">Sin actividad registrada.</p>}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
