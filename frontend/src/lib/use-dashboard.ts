"use client";

import * as React from "react";
import { api } from "@/lib/api";

export type Delta = { current: number; previous: number; pct: number | null };

export type DashboardData = {
  generated_at: string;
  metrics: Record<string, number>;
  deltas: Record<"hires" | "requests" | "attendance_rate", Delta>;
  attendance_funnel: { stage: string; count: number }[];
  weekly_attendance: { date: string; status: string; total: number }[];
  headcount_by_department: { department: string; total: number }[];
  headcount_by_status: { status: string; total: number }[];
  trends: {
    attendance_monthly: { month: string; present: number; late: number; absent: number; rate: number; partial: boolean }[];
    headcount_flow: { month: string; hires: number; terminations: number }[];
    requests_monthly: { month: string; vacations: number; permissions: number; sick_leaves: number }[];
  };
  recent_activity: { id: number; action: string; module?: string; user?: string | null; created_at: string }[];
  upcoming_events: {
    documents: { id: number; name: string; expiration_date: string }[];
    birthdays: { id: number; first_name: string; last_name: string; birth_date: string }[];
  };
};

export function useDashboard() {
  const [data, setData] = React.useState<DashboardData>();
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [fetchedAt, setFetchedAt] = React.useState<Date>();
  const [nonce, setNonce] = React.useState(0);

  React.useEffect(() => {
    const controller = new AbortController();
    queueMicrotask(() => setLoading(true));
    api
      .get<DashboardData>("/dashboard", { signal: controller.signal })
      .then((response) => {
        // Un backend desactualizado responde 200 sin `trends`: no rompas toda la
        // pagina, cae en el estado de error con reintentar.
        if (!response.data?.trends?.attendance_monthly) {
          throw new Error("shape");
        }
        setData(response.data);
        setError(null);
        setFetchedAt(new Date());
      })
      .catch((err) => {
        if (err?.name !== "CanceledError") setError("No se pudo cargar el dashboard.");
      })
      .finally(() => setLoading(false));
    return () => controller.abort();
  }, [nonce]);

  return { data, loading, error, fetchedAt, refresh: () => setNonce((n) => n + 1) };
}
