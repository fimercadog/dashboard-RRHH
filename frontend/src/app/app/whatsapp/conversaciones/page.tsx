"use client";

import { useState, useEffect, useCallback } from "react";
import Link from "next/link";
import { MessageSquare, AlertTriangle, Clock, User } from "lucide-react";
import { api, PaginatedResponse } from "@/lib/api";
import { Badge, badgeVariant, badgeLabel } from "@/components/ui/badge";
import { cn } from "@/lib/utils";

interface Conversation {
  id: number;
  wa_id: string;
  contact_name: string | null;
  status: string;
  is_within_window: boolean;
  last_message_at: string | null;
  last_message_preview: string | null;
  assigned_user: { id: number; name: string } | null;
  created_at: string;
}

type StatusFilter = "all" | "open" | "transferred" | "closed";

const STATUS_FILTERS: { value: StatusFilter; label: string }[] = [
  { value: "all",         label: "Todos" },
  { value: "open",        label: "Abiertos" },
  { value: "transferred", label: "Transferidos" },
  { value: "closed",      label: "Cerrados" },
];

function formatRelative(dateStr: string | null): string {
  if (!dateStr) return "";
  const diff = Date.now() - new Date(dateStr).getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 1)  return "ahora";
  if (mins < 60) return `hace ${mins}m`;
  const hrs = Math.floor(mins / 60);
  if (hrs < 24)  return `hace ${hrs}h`;
  return `hace ${Math.floor(hrs / 24)}d`;
}

export default function ConversacionesPage() {
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [filter, setFilter] = useState<StatusFilter>("all");
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params: Record<string, string | number> = { page };
      if (filter !== "all") params.status = filter;
      const res = await api.get<PaginatedResponse<Conversation>>("/conversations", { params });
      setConversations(res.data.data);
      setLastPage(res.data.meta.last_page);
    } catch {
      setError("No se pudieron cargar las conversaciones.");
    } finally {
      setLoading(false);
    }
  }, [filter, page]);

  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  // Reset page when filter changes
  useEffect(() => { void (async () => { setPage(1); })(); }, [filter]);

  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-xl font-semibold text-foreground">Conversaciones WhatsApp</h1>
        <p className="mt-0.5 text-sm text-muted-foreground">Bandeja de conversaciones entrantes y activas.</p>
      </div>

      {/* Filter bar */}
      <div className="flex flex-wrap gap-2">
        {STATUS_FILTERS.map((f) => (
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
        <div className="py-16 text-center text-sm text-muted-foreground">Cargando conversaciones...</div>
      ) : conversations.length === 0 ? (
        <div className="flex flex-col items-center py-20 text-center">
          <MessageSquare className="h-10 w-10 text-muted-foreground/40" />
          <p className="mt-3 text-sm font-medium text-foreground">Sin conversaciones</p>
          <p className="mt-1 text-xs text-muted-foreground">
            {filter === "all" ? "No hay conversaciones todavía." : `No hay conversaciones con estado "${filter}".`}
          </p>
        </div>
      ) : (
        <div className="overflow-hidden rounded-lg border border-border bg-card">
          {conversations.map((conv, i) => (
            <Link
              key={conv.id}
              href={`/app/whatsapp/conversaciones/${conv.id}`}
              className={cn(
                "flex items-start gap-4 px-4 py-3 transition-colors hover:bg-muted/60",
                i > 0 && "border-t border-border/50",
              )}
            >
              {/* Avatar placeholder */}
              <span className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                {(conv.contact_name ?? conv.wa_id)?.[0]?.toUpperCase() ?? "?"}
              </span>

              <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2">
                  <span className="truncate text-sm font-medium text-foreground">
                    {conv.contact_name ?? conv.wa_id}
                  </span>
                  <Badge variant={badgeVariant(conv.status)}>{badgeLabel(conv.status)}</Badge>
                  {!conv.is_within_window && (
                    <span title="Fuera de ventana de 24 horas">
                      <AlertTriangle className="h-3.5 w-3.5 text-warning" />
                    </span>
                  )}
                </div>
                <p className="mt-0.5 truncate text-xs text-muted-foreground">
                  {conv.last_message_preview ?? "Sin mensajes"}
                </p>
                {conv.assigned_user && (
                  <p className="mt-0.5 flex items-center gap-1 text-[11px] text-muted-foreground/70">
                    <User className="h-3 w-3" />
                    {conv.assigned_user.name}
                  </p>
                )}
              </div>

              <div className="flex shrink-0 flex-col items-end gap-1">
                {conv.last_message_at && (
                  <span className="flex items-center gap-1 text-[11px] text-muted-foreground">
                    <Clock className="h-3 w-3" />
                    {formatRelative(conv.last_message_at)}
                  </span>
                )}
                <span className="text-[10px] text-muted-foreground/60">{conv.wa_id}</span>
              </div>
            </Link>
          ))}
        </div>
      )}

      {/* Pagination */}
      {lastPage > 1 && (
        <div className="flex items-center justify-end gap-2">
          <button
            disabled={page <= 1}
            onClick={() => setPage((p) => p - 1)}
            className="rounded-md border border-border px-3 py-1.5 text-sm text-muted-foreground disabled:opacity-40 hover:bg-muted"
          >
            Anterior
          </button>
          <span className="text-sm text-muted-foreground">
            Página {page} / {lastPage}
          </span>
          <button
            disabled={page >= lastPage}
            onClick={() => setPage((p) => p + 1)}
            className="rounded-md border border-border px-3 py-1.5 text-sm text-muted-foreground disabled:opacity-40 hover:bg-muted"
          >
            Siguiente
          </button>
        </div>
      )}
    </div>
  );
}
