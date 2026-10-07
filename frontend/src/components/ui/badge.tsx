import * as React from "react";
import { cn } from "@/lib/utils";

export type BadgeVariant =
  | "default"
  | "success"
  | "warning"
  | "danger"
  | "info"
  | "primary"
  | "secondary"
  | "muted";

const VARIANT_CLASSES: Record<BadgeVariant, string> = {
  default:   "bg-accent text-foreground",
  success:   "bg-[var(--badge-success-bg)] text-[var(--badge-success-text)]",
  warning:   "bg-[var(--badge-warning-bg)] text-[var(--badge-warning-text)]",
  danger:    "bg-[var(--badge-danger-bg)] text-[var(--badge-danger-text)]",
  info:      "bg-[var(--badge-info-bg)] text-[var(--badge-info-text)]",
  primary:   "bg-[var(--badge-primary-bg)] text-[var(--badge-primary-text)]",
  secondary: "bg-[var(--badge-secondary-bg)] text-[var(--badge-secondary-text)]",
  muted:     "bg-muted text-muted-foreground",
};

const VARIANT_MAP: Record<string, BadgeVariant> = {
  // success
  active: "success", activo: "success",
  paid: "success", pagado: "success",
  posted: "success",
  accepted: "success", aceptado: "success",
  received: "success", recibido: "success",
  fulfilled: "success", entregado: "success",
  selected: "success", seleccionado: "success",
  passed: "success",
  approved: "success", aprobado: "success",
  open: "success",
  done: "success",
  won: "success",
  synced: "success",
  entry: "success",
  income: "success",
  // warning
  pending: "warning", pendiente: "warning",
  partial: "warning", parcial: "warning",
  partially_paid: "warning",
  reviewing: "warning", en_revision: "warning",
  interview: "warning", entrevista: "warning",
  confirmed: "warning",
  in_process: "warning",
  proposal: "warning",
  negotiation: "warning",
  adjustment: "warning",
  // danger
  cancelled: "danger", cancelado: "danger",
  rejected: "danger", rechazado: "danger",
  discarded: "danger", descartado: "danger",
  expired: "danger", vencido: "danger",
  failed: "danger",
  overdue: "danger",
  reversed: "danger",
  lost: "danger",
  exit: "danger",
  expense: "danger",
  // info
  sent: "info", enviado: "info",
  contacted: "info", contactado: "info",
  demo: "info",
  contact: "info",
  prospecting: "info",
  qualification: "info",
  transfer_in: "info",
  // primary
  new: "primary", nuevo: "primary",
  opening: "primary",
  // secondary
  inactive: "secondary", inactivo: "secondary",
  closed: "secondary", cerrado: "secondary",
  stalled: "secondary",
  transfer_out: "secondary",
  terminated: "danger",
  suspended: "secondary",
  on_hold: "secondary",
  // muted
  draft: "muted", borrador: "muted",
  on_leave: "warning",
};

export function badgeVariant(status: string): BadgeVariant {
  return VARIANT_MAP[status.toLowerCase()] ?? "default";
}

const LABEL_MAP: Record<string, string> = {
  active: "Activo", inactive: "Inactivo",
  draft: "Borrador", pending: "Pendiente",
  approved: "Aprobado", rejected: "Rechazado",
  cancelled: "Cancelado", completed: "Completado",
  archived: "Archivado",
  terminated: "Terminado", on_leave: "En licencia",
  suspended: "Suspendido", on_hold: "En espera",
  sent: "Enviado", posted: "Publicado",
  paid: "Pagado", overdue: "Vencido",
  voided: "Anulado", partial: "Parcial",
  partially_paid: "Pago parcial",
  open: "Abierto", closed: "Cerrado", locked: "Bloqueado",
  received: "Recibido", processing: "En proceso",
  new: "Nuevo", contacted: "Contactado",
  qualified: "Calificado", proposal: "Propuesta",
  negotiation: "Negociación", won: "Ganado",
  lost: "Perdido", stalled: "Estancado",
  prospecting: "Prospección", qualification: "Calificación",
  income: "Ingreso", expense: "Egreso",
  transfer_in: "Transferencia entrada",
  transfer_out: "Transferencia salida",
  synced: "Sincronizado", queued: "En cola", failed: "Fallido",
  entry: "Entrada", exit: "Salida",
  adjustment: "Ajuste", opening: "Apertura",
  full_time: "Tiempo completo", part_time: "Tiempo parcial",
  contractor: "Contratista", intern: "Practicante",
  temporary: "Temporal",
  used: "Usado", accrued: "Acumulado",
  admin: "Administrador", manager: "Gerente",
  employee: "Empleado", viewer: "Visualizador",
};

/** Convierte un valor técnico de estado a etiqueta legible. */
export function badgeLabel(status: string): string {
  if (!status) return "";
  return LABEL_MAP[status.toLowerCase()]
    ?? status.replace(/_/g, " ").replace(/\b\w/g, c => c.toUpperCase());
}

export interface BadgeProps extends React.HTMLAttributes<HTMLSpanElement> {
  variant?: BadgeVariant;
}

export function Badge({ className, variant = "default", ...props }: BadgeProps) {
  return (
    <span
      className={cn(
        "inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap",
        VARIANT_CLASSES[variant],
        className,
      )}
      {...props}
    />
  );
}
