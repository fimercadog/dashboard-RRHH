"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { ArrowLeft } from "lucide-react";
import { Badge, badgeVariant, badgeLabel } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { api } from "@/lib/api";
import { Employee } from "@/lib/types";

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

export function EmployeeProfileClient() {
  const { id } = useParams<{ id: string }>();
  const router = useRouter();
  const [employee, setEmployee] = useState<Employee | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api
      .get<{ data: Employee }>(`/employees/${id}`)
      .then((res) => setEmployee(res.data.data))
      .catch(() => setError("No se pudo cargar el empleado."))
      .finally(() => setLoading(false));
  }, [id]);

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
    </div>
  );
}
