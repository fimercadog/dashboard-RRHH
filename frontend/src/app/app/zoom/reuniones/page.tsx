"use client";

import { useState, useEffect } from "react";
import { Video } from "lucide-react";
import { api } from "@/lib/api";
import { cn } from "@/lib/utils";

interface Meeting {
  id: number;
  topic: string;
  start_time: string;
  duration: number;
  status: string;
  join_url: string | null;
}

type StatusFilter = "all" | "upcoming" | "past";

const FILTERS: { value: StatusFilter; label: string }[] = [
  { value: "all",      label: "Todas" },
  { value: "upcoming", label: "Próximas" },
  { value: "past",     label: "Pasadas" },
];

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleString("es-CO", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

export default function ReunionesZoomPage() {
  const [meetings, setMeetings] = useState<Meeting[]>([]);
  const [filter, setFilter] = useState<StatusFilter>("all");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);
    api
      .get<Meeting[]>("/zoom/meetings", { params: filter !== "all" ? { status: filter } : {} })
      .then((res) => setMeetings(res.data))
      .catch(() => setError("No se pudieron cargar las reuniones."))
      .finally(() => setLoading(false));
  }, [filter]);

  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-xl font-semibold text-foreground">Reuniones Zoom</h1>
        <p className="mt-0.5 text-sm text-muted-foreground">Reuniones programadas y activas.</p>
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
        <div className="py-16 text-center text-sm text-muted-foreground">Cargando reuniones...</div>
      ) : meetings.length === 0 ? (
        <div className="flex flex-col items-center py-20 text-center">
          <Video className="h-10 w-10 text-muted-foreground/40" />
          <p className="mt-3 text-sm font-medium text-foreground">Sin reuniones</p>
          <p className="mt-1 text-xs text-muted-foreground">
            No hay reuniones todavía. Configura tu cuenta de Zoom para comenzar.
          </p>
        </div>
      ) : (
        <div className="overflow-hidden rounded-lg border border-border bg-card">
          {meetings.map((meeting, i) => (
            <div
              key={meeting.id}
              className={cn(
                "flex items-start gap-4 px-4 py-3",
                i > 0 && "border-t border-border/50",
              )}
            >
              <span className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-500/10 text-sm font-semibold text-blue-500">
                <Video className="h-4 w-4" />
              </span>
              <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium text-foreground">{meeting.topic}</p>
                <p className="mt-0.5 text-xs text-muted-foreground">{formatDate(meeting.start_time)} · {meeting.duration} min</p>
              </div>
              {meeting.join_url && (
                <a
                  href={meeting.join_url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="shrink-0 rounded-md bg-blue-500/10 px-3 py-1 text-xs font-medium text-blue-500 hover:bg-blue-500/20"
                >
                  Unirse
                </a>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
