"use client";

import * as React from "react";
import Link from "next/link";
import { Bell, ArrowRight, Clock } from "lucide-react";
import { Button } from "@/components/ui/button";
import { usePending, type PendingItem } from "@/hooks/use-pending";
import { cn } from "@/lib/utils";

const PRIORITY_COLOR: Record<string, string> = {
  high:   "bg-destructive/10 text-destructive",
  medium: "bg-warning/10 text-warning",
  low:    "bg-muted text-muted-foreground",
};

function timeAgo(dateStr: string | null): string {
  if (!dateStr) return "";
  const diff = Date.now() - new Date(dateStr).getTime();
  const days = Math.floor(diff / 86_400_000);
  if (days === 0) return "hoy";
  if (days === 1) return "ayer";
  return `hace ${days} días`;
}

export function PendingButton() {
  const { items, total, loading } = usePending();
  const [open, setOpen] = React.useState(false);
  const ref = React.useRef<HTMLDivElement>(null);

  // Close on outside click.
  React.useEffect(() => {
    function handle(e: MouseEvent) {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
    }
    document.addEventListener("mousedown", handle);
    return () => document.removeEventListener("mousedown", handle);
  }, []);

  return (
    <div ref={ref} className="relative">
      <Button
        variant="outline"
        size="icon"
        aria-label={total > 0 ? `${total} procesos pendientes` : "Sin procesos pendientes"}
        title="Procesos pendientes"
        onClick={() => setOpen((p) => !p)}
        className="relative"
      >
        <Bell className="h-4 w-4" />
        {!loading && total > 0 && (
          <span
            aria-hidden
            className="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-bold text-white"
          >
            {total > 99 ? "99+" : total}
          </span>
        )}
      </Button>

      {open && (
        <div className="absolute right-0 top-full z-50 mt-2 w-80 overflow-hidden rounded-xl border border-border bg-card shadow-lg sm:w-96">
          <div className="border-b border-border px-4 py-3">
            <p className="text-sm font-semibold text-foreground">Procesos pendientes</p>
            {total > 0 && (
              <p className="text-xs text-muted-foreground">{total} {total === 1 ? "acción requiere" : "acciones requieren"} atención</p>
            )}
          </div>

          <div className="max-h-[420px] overflow-y-auto">
            {loading ? (
              <div className="px-4 py-6 text-center text-sm text-muted-foreground">Cargando...</div>
            ) : items.length === 0 ? (
              <div className="px-4 py-8 text-center">
                <Bell className="mx-auto h-8 w-8 text-muted-foreground/40" />
                <p className="mt-2 text-sm font-medium text-foreground">Sin pendientes</p>
                <p className="mt-1 text-xs text-muted-foreground">Todo está al día.</p>
              </div>
            ) : (
              <ul className="divide-y divide-border">
                {items.map((item: PendingItem) => (
                  <li key={item.type}>
                    <Link
                      href={item.route}
                      onClick={() => setOpen(false)}
                      className="flex items-start gap-3 px-4 py-3 transition-colors hover:bg-muted/50"
                    >
                      <span
                        className={cn(
                          "mt-0.5 flex h-6 min-w-6 items-center justify-center rounded-full text-[11px] font-bold",
                          PRIORITY_COLOR[item.priority] ?? PRIORITY_COLOR.low,
                        )}
                      >
                        {item.count > 99 ? "99+" : item.count}
                      </span>
                      <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-medium text-foreground">{item.title}</p>
                        <p className="truncate text-xs text-muted-foreground">{item.description}</p>
                        {item.date && (
                          <p className="mt-0.5 flex items-center gap-1 text-[10px] text-muted-foreground/70">
                            <Clock className="h-3 w-3" />
                            {timeAgo(item.date)}
                          </p>
                        )}
                      </div>
                      <ArrowRight className="mt-1 h-3.5 w-3.5 shrink-0 text-muted-foreground/50" />
                    </Link>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
