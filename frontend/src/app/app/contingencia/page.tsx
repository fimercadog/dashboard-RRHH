"use client";

import * as React from "react";
import { toast } from "sonner";
import { AlertTriangle, RefreshCw, Trash2, WifiOff } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useContingency } from "@/lib/contingency/context";
import { getStoredUser, hasAnyPermission } from "@/lib/auth";
import { QueuedTxStatus } from "@/lib/contingency/types";

const statusBadge: Record<QueuedTxStatus, string> = {
  pending: "Pendiente",
  synced: "Sincronizado",
  failed: "Con error",
};

function errorMessage(error: unknown, fallback: string) {
  if (error && typeof error === "object" && "response" in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response;
    if (response?.data?.message) return response.data.message;
  }
  return error instanceof Error ? error.message : fallback;
}

export default function ContingencyPage() {
  const { status, loading, isActive, queue, pendingCount, enabledModules, activate, deactivate, syncOne, discardOne } =
    useContingency();
  const [selected, setSelected] = React.useState<string[]>([]);
  const [busy, setBusy] = React.useState(false);

  const canManage = React.useMemo(() => hasAnyPermission(getStoredUser(), ["settings.manage"]), []);
  const modules = status?.modules ?? [];

  function toggle(key: string) {
    setSelected((prev) => (prev.includes(key) ? prev.filter((k) => k !== key) : [...prev, key]));
  }

  async function handleActivate() {
    if (!selected.length) {
      toast.error("Selecciona al menos un modulo.");
      return;
    }
    setBusy(true);
    try {
      await activate(selected);
      toast.success("Modo contingencia activado.");
      setSelected([]);
    } catch (error) {
      toast.error(errorMessage(error, "No se pudo activar."));
    } finally {
      setBusy(false);
    }
  }

  async function handleDeactivate() {
    setBusy(true);
    try {
      await deactivate();
      toast.success("Operacion normal restablecida.");
    } catch (error) {
      toast.error(errorMessage(error, "No se pudo desactivar."));
    } finally {
      setBusy(false);
    }
  }

  async function handleSync(id: string) {
    try {
      await syncOne(id);
      toast.success("Transaccion sincronizada.");
    } catch (error) {
      toast.error(errorMessage(error, "Fallo la sincronizacion."));
    }
  }

  async function handleSyncAll() {
    const ids = queue.filter((tx) => tx.status === "pending" || tx.status === "failed").map((tx) => tx.id);
    for (const id of ids) {
      try {
        await syncOne(id);
      } catch {
        // Se detiene el resto: el usuario revisa el error antes de continuar.
        toast.error("Se detuvo la sincronizacion en una transaccion con error.");
        return;
      }
    }
    if (ids.length) toast.success("Cola sincronizada.");
  }

  async function handleDiscard(id: string) {
    const reason = window.prompt("Motivo del descarte (queda como evidencia):");
    if (reason == null) return;
    try {
      await discardOne(id, reason);
      toast.success("Transaccion descartada.");
    } catch (error) {
      toast.error(errorMessage(error, "No se pudo descartar."));
    }
  }

  if (loading && !status) {
    return <p className="text-sm text-muted-foreground">Cargando estado...</p>;
  }

  return (
    <div className="space-y-6">
      <div className="flex items-start gap-3">
        <span className="grid size-10 shrink-0 place-items-center rounded-lg bg-warning/15 text-warning">
          <WifiOff className="size-5" />
        </span>
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">Modo contingencia</h1>
          <p className="text-sm text-muted-foreground">
            Mantiene modulos seguros operando en local durante una caida de conexion. Los registros se encolan y se
            sincronizan manualmente al volver la conexion.
          </p>
        </div>
      </div>

      <Card>
        <CardHeader className="flex-row items-center justify-between">
          <CardTitle>Estado</CardTitle>
          <Badge className={isActive ? "bg-warning/15 text-warning" : undefined}>
            {isActive ? "Activo" : "Inactivo"}
          </Badge>
        </CardHeader>
        <CardContent className="space-y-4">
          {isActive && status?.session ? (
            <div className="space-y-1 text-sm">
              <p>
                Modulos habilitados: <strong>{enabledModules.join(", ")}</strong>
              </p>
              <p className="text-muted-foreground">
                Activado por {status.session.activated_by?.name ?? "—"} ·{" "}
                {new Date(status.session.activated_at).toLocaleString("es-CO")}
              </p>
            </div>
          ) : (
            <p className="text-sm text-muted-foreground">La operacion funciona con normalidad contra el API.</p>
          )}

          {!canManage ? (
            <p className="text-sm text-muted-foreground">Solo un administrador puede activar o desactivar este modo.</p>
          ) : isActive ? (
            <div className="space-y-2">
              {pendingCount > 0 ? (
                <p className="flex items-center gap-2 text-sm text-warning">
                  <AlertTriangle className="h-4 w-4" />
                  Hay {pendingCount} transaccion(es) sin sincronizar. Resuelvelas antes de desactivar.
                </p>
              ) : null}
              <Button variant="destructive" onClick={handleDeactivate} disabled={busy || pendingCount > 0}>
                Desactivar y volver a la normalidad
              </Button>
            </div>
          ) : (
            <div className="space-y-3">
              <p className="text-sm font-medium">Modulos a habilitar</p>
              <div className="space-y-2">
                {modules.map((module) => (
                  <label key={module.key} className="flex items-start gap-3 rounded-md border border-border p-3 text-sm">
                    <input
                      type="checkbox"
                      className="mt-1"
                      checked={selected.includes(module.key)}
                      onChange={() => toggle(module.key)}
                    />
                    <span>
                      <span className="font-medium">{module.label}</span>
                      <span className="block text-muted-foreground">{module.description}</span>
                    </span>
                  </label>
                ))}
              </div>
              <Button onClick={handleActivate} disabled={busy}>
                Activar modo contingencia
              </Button>
            </div>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex-row items-center justify-between">
          <CardTitle>Cola local ({queue.length})</CardTitle>
          {queue.some((tx) => tx.status === "pending" || tx.status === "failed") ? (
            <Button variant="outline" size="sm" onClick={handleSyncAll}>
              <RefreshCw className="h-4 w-4" /> Sincronizar todo
            </Button>
          ) : null}
        </CardHeader>
        <CardContent className="space-y-3">
          {queue.length ? (
            queue.map((tx) => (
              <div
                key={tx.id}
                className="flex flex-col gap-2 border-b border-border pb-3 last:border-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
              >
                <div className="min-w-0">
                  <p className="truncate text-sm font-medium">{tx.summary}</p>
                  <p className="text-xs text-muted-foreground">
                    {tx.module} · {new Date(tx.createdAt).toLocaleString("es-CO")}
                    {tx.error ? ` · ${tx.error}` : ""}
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  <Badge className={tx.status === "failed" ? "bg-destructive/15 text-destructive" : undefined}>
                    {statusBadge[tx.status]}
                  </Badge>
                  <Button variant="outline" size="sm" onClick={() => handleSync(tx.id)}>
                    <RefreshCw className="h-4 w-4" /> Sincronizar
                  </Button>
                  <Button variant="ghost" size="sm" onClick={() => handleDiscard(tx.id)}>
                    <Trash2 className="h-4 w-4" /> Descartar
                  </Button>
                </div>
              </div>
            ))
          ) : (
            <p className="text-sm text-muted-foreground">No hay transacciones encoladas.</p>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
