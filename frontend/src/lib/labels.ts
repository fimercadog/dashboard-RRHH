/** Mapa centralizado: valor DB (inglés) → etiqueta para UI (español) */

const STATUS: Record<string, string> = {
  // Solicitudes / flujos RRHH
  pending:    "Pendiente",
  approved:   "Aprobado",
  rejected:   "Rechazado",
  cancelled:  "Cancelado",
  // Empleados / org
  inactive:   "Inactivo",
  terminated: "Terminado",
  // Asistencia
  present:    "Presente",
  late:       "Tarde",
  absent:     "Ausente",
  permission: "Permiso",
  vacation:   "Vacaciones",
  sick_leave: "Incapacidad",
  // Contratación
  open:       "Abierta",
  closed:     "Cerrada",
  draft:      "Borrador",
  // Incapacidades / genérico activo
  active:     "Activo",
  // Documentos
  valid:      "Válido",
  expired:    "Vencido",
  expiring:   "Por vencer",
  // Inventario / finanzas / compras / ventas
  published:  "Publicado",
  unpublished:"No publicado",
  received:   "Recibida",
  partial:    "Parcial",
  paid:       "Pagada",
  unpaid:     "Sin pagar",
  overdue:    "Vencida",
  won:        "Ganado",
  lost:       "Perdido",
  // Genérico
  enabled:    "Habilitado",
  disabled:   "Deshabilitado",
};

export function statusLabel(status: string | null | undefined): string {
  if (!status) return "—";
  return STATUS[status.toLowerCase()] ?? (status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, " "));
}

// Audit log action labels (DB stores snake_case English)
const ACTION: Record<string, string> = {
  login:                     "Inicio de sesión",
  logout:                    "Cierre de sesión",
  created:                   "Creado",
  updated:                   "Actualizado",
  deleted:                   "Eliminado",
  exported:                  "Exportado",
  // Compuestos que guarda el AuditService
  employee_created:          "Empleado creado",
  employee_updated:          "Empleado actualizado",
  employee_deleted:          "Empleado eliminado",
  attendance_exported:       "Asistencia exportada",
  sick_leave_created:        "Incapacidad creada",
  sick_leave_updated:        "Incapacidad actualizada",
  document_uploaded:         "Documento subido",
  document_updated:          "Documento actualizado",
  vacation_request_created:  "Vacación solicitada",
  vacation_request_updated:  "Vacación actualizada",
  permission_request_created:"Permiso solicitado",
  permission_request_updated:"Permiso actualizado",
};

export function actionLabel(action: string | null | undefined): string {
  if (!action) return "—";
  const key = action.toLowerCase();
  return ACTION[key] ?? (action.charAt(0).toUpperCase() + action.slice(1).replace(/[._]/g, " "));
}
