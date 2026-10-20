"use client";

import { useCallback, useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { ArrowLeft, Pencil, Plus, Trash2 } from "lucide-react";
import { toast } from "sonner";
import { Badge, badgeVariant, badgeLabel } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { CrudModal } from "@/components/crud/crud-modal";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { api } from "@/lib/api";
import { Employee, EmployeeWorkHistory } from "@/lib/types";

const fmt = new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 });

function Row({ label, value }: { label: string; value?: string | number | null }) {
  if (!value && value !== 0) return null;
  return (
    <div className="flex flex-col gap-0.5 sm:flex-row sm:gap-4">
      <span className="w-40 shrink-0 text-sm text-muted-foreground">{label}</span>
      <span className="text-sm">{String(value)}</span>
    </div>
  );
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <div className="rounded-lg border bg-card p-4 space-y-3">
      <h2 className="font-semibold text-base">{title}</h2>
      <div className="space-y-2">{children}</div>
    </div>
  );
}

const EVENT_TYPE_LABEL: Record<string, string> = {
  hire: "Contratación",
  promotion: "Ascenso",
  salary_adjustment: "Ajuste salarial",
  recognition: "Reconocimiento",
  other: "Otro",
};

const WORK_HISTORY_FIELDS = [
  {
    name: "event_type",
    label: "Tipo de evento",
    type: "select" as const,
    required: true,
    options: [
      { label: "Contratación", value: "hire" },
      { label: "Ascenso", value: "promotion" },
      { label: "Ajuste salarial", value: "salary_adjustment" },
      { label: "Reconocimiento", value: "recognition" },
      { label: "Otro", value: "other" },
    ],
  },
  { name: "title", label: "Título", required: true, colSpan: "full" as const },
  { name: "effective_date", label: "Fecha efectiva", type: "date" as const, required: true },
  { name: "description", label: "Descripción", type: "textarea" as const, colSpan: "full" as const },
];

type Tab = "ficha" | "historial" | "documentos";

export function EmployeeProfileClient() {
  const { id } = useParams<{ id: string }>();
  const router = useRouter();
  const [employee, setEmployee] = useState<Employee | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [tab, setTab] = useState<Tab>("ficha");

  // Work history modal state
  const [whModal, setWhModal] = useState<{ open: boolean; mode: "create" | "edit"; row: EmployeeWorkHistory | null }>({
    open: false, mode: "create", row: null,
  });
  const [deleteTarget, setDeleteTarget] = useState<EmployeeWorkHistory | null>(null);
  const [deleting, setDeleting] = useState(false);

  const loadEmployee = useCallback(() => {
    api
      .get<{ data: Employee }>(`/employees/${id}`)
      .then((res) => setEmployee(res.data.data))
      .catch(() => setError("No se pudo cargar el empleado."))
      .finally(() => setLoading(false));
  }, [id]);

  useEffect(() => { loadEmployee(); }, [loadEmployee]);

  async function confirmDelete() {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await api.delete(`/employees/${id}/work-history/${deleteTarget.id}`);
      toast.success("Entrada eliminada");
      setDeleteTarget(null);
      loadEmployee();
    } catch {
      toast.error("No se pudo eliminar la entrada.");
    } finally {
      setDeleting(false);
    }
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center h-48">
        <span className="text-muted-foreground text-sm">Cargando...</span>
      </div>
    );
  }

  if (error || !employee) {
    return (
      <div className="space-y-4">
        <Button variant="ghost" size="sm" onClick={() => router.back()}>
          <ArrowLeft className="h-4 w-4 mr-2" /> Volver
        </Button>
        <p className="text-destructive text-sm">{error ?? "Empleado no encontrado."}</p>
      </div>
    );
  }

  const fullName = employee.full_name ?? `${employee.first_name} ${employee.last_name}`;

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => router.back()}>
          <ArrowLeft className="h-4 w-4 mr-2" /> Empleados
        </Button>
      </div>

      <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">{fullName}</h1>
          <p className="text-muted-foreground text-sm">{employee.employee_code}</p>
        </div>
        <Badge variant={badgeVariant(employee.employment_status)}>{badgeLabel(employee.employment_status)}</Badge>
      </div>

      {/* Tabs */}
      <div className="flex gap-1 border-b">
        {(["ficha", "historial", "documentos"] as Tab[]).map((t) => (
          <button
            key={t}
            onClick={() => setTab(t)}
            className={`px-4 py-2 text-sm font-medium capitalize border-b-2 -mb-px transition-colors ${
              tab === t
                ? "border-primary text-primary"
                : "border-transparent text-muted-foreground hover:text-foreground"
            }`}
          >
            {t === "ficha" ? "Ficha" : t === "historial" ? "Historial laboral" : "Documentos"}
          </button>
        ))}
      </div>

      {/* Tab: Ficha */}
      {tab === "ficha" && (
        <div className="grid gap-4 sm:grid-cols-2">
          <Section title="Informacion personal">
            <Row label="Tipo documento" value={employee.identification_type} />
            <Row label="Numero documento" value={employee.identification_number} />
            <Row label="Genero" value={employee.gender} />
            <Row label="Fecha nacimiento" value={employee.birth_date} />
            <Row label="Correo" value={employee.email} />
            <Row label="Telefono" value={employee.phone} />
            <Row label="Ciudad" value={employee.city} />
            <Row label="Direccion" value={employee.address} />
          </Section>

          <Section title="Informacion laboral">
            <Row label="Area" value={employee.department?.name} />
            <Row label="Cargo" value={employee.position?.name} />
            <Row label="Jefe directo" value={employee.manager?.full_name ?? (employee.manager ? `${employee.manager.first_name} ${employee.manager.last_name}` : undefined)} />
            <Row label="Tipo contrato" value={employee.contract_type} />
            <Row label="Horario" value={employee.work_schedule} />
            <Row label="Fecha ingreso" value={employee.hire_date} />
            <Row label="Fecha terminacion" value={employee.termination_date} />
            <Row label="Salario" value={employee.salary ? fmt.format(employee.salary) : undefined} />
          </Section>

          <Section title="Seguridad social">
            <Row label="EPS" value={employee.eps} />
            <Row label="ARL" value={employee.arl} />
            <Row label="Pension" value={employee.pension_fund} />
            <Row label="Caja compensacion" value={employee.compensation_fund} />
          </Section>

          <Section title="Contacto de emergencia">
            <Row label="Nombre" value={employee.emergency_contact_name} />
            <Row label="Telefono" value={employee.emergency_contact_phone} />
          </Section>

          {employee.notes && (
            <Section title="Notas">
              <p className="text-sm whitespace-pre-wrap">{employee.notes}</p>
            </Section>
          )}
        </div>
      )}

      {/* Tab: Historial laboral */}
      {tab === "historial" && (
        <div className="space-y-3">
          <div className="flex justify-end">
            <Button size="sm" onClick={() => setWhModal({ open: true, mode: "create", row: null })}>
              <Plus className="h-4 w-4 mr-1" /> Agregar entrada
            </Button>
          </div>

          {!employee.work_history?.length ? (
            <p className="text-sm text-muted-foreground">Sin historial laboral registrado.</p>
          ) : (
            employee.work_history.map((item) => (
              <div key={item.id} className="rounded-lg border bg-card p-4 space-y-1">
                <div className="flex items-start justify-between gap-2">
                  <div className="space-y-0.5">
                    <span className="font-medium text-sm">{item.title}</span>
                    <div className="flex items-center gap-2">
                      <span className="text-xs text-muted-foreground">{EVENT_TYPE_LABEL[item.event_type] ?? item.event_type}</span>
                      <span className="text-xs text-muted-foreground">·</span>
                      <span className="text-xs text-muted-foreground">{item.effective_date}</span>
                    </div>
                  </div>
                  <div className="flex gap-1 shrink-0">
                    <Button
                      variant="ghost" size="icon"
                      className="h-7 w-7"
                      onClick={() => setWhModal({ open: true, mode: "edit", row: item })}
                    >
                      <Pencil className="h-3.5 w-3.5" />
                    </Button>
                    <Button
                      variant="ghost" size="icon"
                      className="h-7 w-7 text-destructive hover:text-destructive"
                      onClick={() => setDeleteTarget(item)}
                    >
                      <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                  </div>
                </div>
                {item.description && <p className="text-sm mt-1 text-muted-foreground">{item.description}</p>}
              </div>
            ))
          )}
        </div>
      )}

      {/* Tab: Documentos */}
      {tab === "documentos" && (
        <div className="space-y-3">
          {!employee.documents?.length ? (
            <p className="text-sm text-muted-foreground">Sin documentos registrados.</p>
          ) : (
            employee.documents.map((doc) => (
              <div key={doc.id} className="rounded-lg border bg-card p-4 space-y-1">
                <div className="flex items-center justify-between gap-2">
                  <span className="font-medium text-sm">{doc.name}</span>
                  <Badge variant={badgeVariant(doc.status)}>{badgeLabel(doc.status)}</Badge>
                </div>
                <span className="text-xs text-muted-foreground">{doc.document_type}</span>
                {doc.expiration_date && (
                  <p className="text-xs text-muted-foreground">Vence: {doc.expiration_date}</p>
                )}
              </div>
            ))
          )}
        </div>
      )}

      {/* Modal crear / editar entrada de historial */}
      <CrudModal
        open={whModal.open}
        onOpenChange={(open) => setWhModal((s) => ({ ...s, open }))}
        mode={whModal.mode}
        title={whModal.mode === "create" ? "Agregar entrada al historial" : "Editar entrada"}
        resource={`/employees/${id}/work-history`}
        fields={WORK_HISTORY_FIELDS}
        row={whModal.row as Record<string, unknown> | null}
        onSaved={() => { setWhModal((s) => ({ ...s, open: false })); loadEmployee(); }}
      />

      {/* Confirmación de eliminación */}
      <Dialog open={!!deleteTarget} onOpenChange={(open) => { if (!open) setDeleteTarget(null); }}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Eliminar entrada</DialogTitle>
            <DialogDescription>
              ¿Eliminar &ldquo;{deleteTarget?.title}&rdquo;? Esta acción no se puede deshacer.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleteTarget(null)} disabled={deleting}>
              Cancelar
            </Button>
            <Button variant="destructive" onClick={confirmDelete} disabled={deleting}>
              {deleting ? "Eliminando..." : "Eliminar"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
