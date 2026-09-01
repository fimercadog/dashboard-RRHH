"use client";

import Link from "next/link";
import { WifiOff } from "lucide-react";
import { useContingency } from "@/lib/contingency/context";
import { cn } from "@/lib/utils";

// Banner persistente mientras la contingencia esta activa. No es un toast:
// el usuario nunca debe confundir el modo contingencia con la operacion normal.
export function ContingencyBanner() {
  const { isActive, status, queue, pendingCount } = useContingency();

  if (!isActive || !status?.session) return null;

  const session = status.session;
  const failed = queue.filter((tx) => tx.status === "failed").length;
  const labelByKey = new Map(status.modules.map((m) => [m.key, m.label]));
  const enabled = session.enabled_modules.map((k) => labelByKey.get(k) ?? k).join(", ");
  const activatedAt = new Date(session.activated_at).toLocaleString("es-CO");

  return (
    <div
      className={cn(
        "flex flex-wrap items-center gap-x-4 gap-y-1 border-b px-4 py-2 text-xs lg:px-6",
        failed > 0
          ? "border-destructive/50 bg-destructive/10 text-destructive"
          : "border-warning/50 bg-warning/10 text-warning",
      )}
    >
      <span className="flex items-center gap-1.5 font-semibold uppercase tracking-wide">
        <WifiOff className="h-3.5 w-3.5" /> Modo contingencia
      </span>
      <span>
        Modulos: <strong>{enabled}</strong>
      </span>
      <span>
        Pendientes: <strong className="tabular-nums">{pendingCount}</strong>
        {failed > 0 ? <strong className="tabular-nums"> ({failed} con error)</strong> : null}
      </span>
      <span className="hidden sm:inline">
        Activado por {session.activated_by?.name ?? "—"} · {activatedAt}
      </span>
      <Link href="/app/contingencia" className="ml-auto font-semibold underline underline-offset-2">
        Gestionar
      </Link>
    </div>
  );
}
