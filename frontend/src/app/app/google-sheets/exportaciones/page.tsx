"use client";

import { useState, useEffect } from "react";
import { Sheet } from "lucide-react";
import { api } from "@/lib/api";
import { cn } from "@/lib/utils";

interface Export {
  id: number;
  name: string;
  sheet_name: string;
  last_synced_at: string | null;
  rows_exported: number | null;
  status: string;
}

type StatusFilter = "all" | "synced" | "pending" | "error";

const FILTERS: { value: StatusFilter; label: string }[] = [
  { value: "all",     label: "Todas" },
  { value: "synced",  label: "Sincronizadas" },
  { value: "pending", label: "Pendientes" },
  { value: "error",   label: "Con error" },
];

const STATUS_COLORS: Record<string, string> = {
  synced:  "text-green-600 bg-green-600/10",
  pending: "text-yellow-600 bg-yellow-600/10",
  error:   "text-destructive bg-destructive/10",
};

const STATUS_LABELS: Record<string, string> = {
  synced:  "Sincronizado",
  pending: "Pendiente",
  error:   "Error",
};

function formatDate(dateStr: string | null): string {
  if (!dateStr) return "Nunca";
  return new Date(dateStr).toLocaleString("es-CO", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

export default function ExportacionesGSheetsPage() {
  const [exports, setExports] = useState<Export[]>([]);
  const [filter, setFilter] = useState<StatusFilter>("all");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);
    api
      .get<Export[]>("/google-sheets/exports", { params: filter !== "all" ? { status: filter } : {} })
      .then((res) => setExports(res.data))
      .catch(() => setError("No se pudieron cargar las exportaciones."))
      .finally(() => setLoading(false));
  }, [filter]);

  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-xl font-semibold text-foreground">Exportaciones Google Sheets</h1>
        <p className="mt-0.5 text-sm text-muted-foreground">Datos sincronizados con hojas de cálculo.</p>
      </div>

      <div className="flex flex-wrap gap-2">
        {FILTERS.map((f) => (
          <button
            key={f.value}
            onClick={() => setFilter(f.value)}
            className={cn(
              "rounded-md px-3 py-1.5 text-sm font-medium transition-colors",
              filter === f.value
                ? "bg-primary text-primary-foreground"
                : "bg-muted text-muted-foreground hover:bg-border hover:text-foreground",
            )}
          >
            {f.label}
          </button>
        ))}
      </div>

      {error && (
        <div className="rounded-md border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
          {error}
        </div>
      )}

      {loading ? (
        <div className="py-16 text-center text-sm text-muted-foreground">Cargando exportaciones...</div>
      ) : exports.length === 0 ? (
        <div className="flex flex-col items-center py-20 text-center">
          <Sheet className="h-10 w-10 text-muted-foreground/40" />
          <p className="mt-3 text-sm font-medium text-foreground">Sin exportaciones</p>
          <p className="mt-1 text-xs text-muted-foreground">
            No hay exportaciones configuradas. Conecta Google Sheets para comenzar.
          </p>
        </div>
      ) : (
        <div className="overflow-hidden rounded-lg border border-border bg-card">
          {exports.map((exp, i) => (
            <div
              key={exp.id}
              className={cn(
                "flex items-start gap-4 px-4 py-3",
                i > 0 && "border-t border-border/50",
              )}
            >
              <span className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-600/10 text-sm font-semibold text-green-600">
                <Sheet className="h-4 w-4" />
              </span>
              <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium text-foreground">{exp.name}</p>
                <p className="mt-0.5 text-xs text-muted-foreground">
                  Hoja: {exp.sheet_name} · Última sync: {formatDate(exp.last_synced_at)}
                  {exp.rows_exported != null ? ` · ${exp.rows_exported} filas` : ""}
                </p>
              </div>
              <span className={cn("shrink-0 rounded-md px-2 py-0.5 text-xs font-medium", STATUS_COLORS[exp.status] ?? "text-muted-foreground bg-muted")}>
                {STATUS_LABELS[exp.status] ?? exp.status}
              </span>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
