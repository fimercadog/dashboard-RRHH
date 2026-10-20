"use client";

import { useState } from "react";
import { Eye } from "lucide-react";
import Link from "next/link";
import { CrudField } from "@/components/crud/crud-modal";
import { ToggleStatusAction } from "@/components/crud/toggle-status-action";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant, badgeLabel } from "@/components/ui/badge";
import { EMPLOYEE_IDENTIFICATION_TYPE_OPTIONS, STATUS_OPTIONS } from "@/lib/constants";
import { AppColumnDef } from "@/lib/table-types";
import { Employee } from "@/lib/types";

const STATUS_FILTER_OPTIONS = [
  { value: "", label: "Todos los estados" },
  { value: "active", label: "Activo" },
  { value: "pending_withdrawal", label: "En proceso de retiro" },
  { value: "inactive", label: "Inactivo" },
  { value: "on_leave", label: "En licencia" },
  { value: "terminated", label: "Terminado" },
];

const columns: AppColumnDef<Employee>[] = [
  { accessorKey: "employee_code", header: "Codigo" },
  { header: "Nombre", cell: ({ row }) => row.original.full_name ?? `${row.original.first_name} ${row.original.last_name}` },
  { accessorKey: "email", header: "Correo" },
  { header: "Area", cell: ({ row }) => row.original.department?.name ?? "Sin area" },
  { header: "Cargo", cell: ({ row }) => row.original.position?.name ?? "Sin cargo" },
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.employment_status)}>{badgeLabel(row.original.employment_status)}</Badge> },
];

const NAME_PATTERN = "[A-Za-zÀ-ÿ][A-Za-zÀ-ÿ '.\\-]*";

const fields: CrudField[] = [
  { name: "employee_code", label: "Codigo", required: true, pattern: "[A-Za-z0-9\\-]+", hint: "Letras, numeros y guion" },
  { name: "first_name", label: "Nombres", required: true, pattern: NAME_PATTERN, hint: "Solo letras" },
  { name: "last_name", label: "Apellidos", required: true, pattern: NAME_PATTERN, hint: "Solo letras" },
  { name: "identification_type", label: "Tipo de documento", type: "select", required: true, options: EMPLOYEE_IDENTIFICATION_TYPE_OPTIONS },
  { name: "identification_number", label: "Numero de documento", required: true, pattern: "[0-9][0-9.\\-]*", hint: "Solo numeros" },
  { name: "email", label: "Correo", type: "email", hint: "ejemplo@dominio.com" },
  { name: "hire_date", label: "Fecha de ingreso", type: "date", required: true },
  {
    name: "employment_status",
    label: "Estado",
    type: "select",
    required: true,
    defaultValue: "active",
    createHidden: true,
    options: [
      ...STATUS_OPTIONS,
      { label: "Terminado", value: "terminated" },
      { label: "En licencia", value: "on_leave" },
      { label: "En proceso de retiro", value: "pending_withdrawal" },
    ],
  },
  { name: "eps", label: "EPS" },
  { name: "arl", label: "ARL" },
  { name: "pension_fund", label: "Fondo de pension" },
  { name: "compensation_fund", label: "Caja de compensacion" },
  { name: "department_id", label: "Area", type: "relation-select", endpoint: "/departments?per_page=100" },
  { name: "position_id", label: "Cargo", type: "relation-select", endpoint: "/positions?per_page=100" },
  { name: "salary", label: "Salario", type: "number", min: 0, hint: "Solo numeros, sin negativos" },
];

export default function EmployeesPage() {
  const [statusFilter, setStatusFilter] = useState("");

  const extraParams = statusFilter ? { status: statusFilter } : undefined;

  const filterSlot = (
    <select
      value={statusFilter}
      onChange={(e) => setStatusFilter(e.target.value)}
      className="w-52 rounded-md border border-input bg-background px-3 py-2 text-sm"
    >
      {STATUS_FILTER_OPTIONS.map((opt) => (
        <option key={opt.value} value={opt.value}>{opt.label}</option>
      ))}
    </select>
  );

  return (
    <ModuleTablePage
      title="Empleados"
      description="Listado central con busqueda, paginacion backend y exportaciones."
      resource="/employees"
      exportResource="employees"
      columns={columns}
      fields={fields}
      actionLabel="Nuevo empleado"
      modalDescription="Captura la informacion basica del colaborador."
      extraParams={extraParams}
      filterSlot={filterSlot}
      extraRowActions={(row, refresh) => (
        <>
          <Link href={`/app/empleados/${row.id}`} className="inline-flex h-8 items-center gap-2 rounded-md px-2 text-sm hover:bg-muted">
            <Eye className="h-4 w-4" /> Ver
          </Link>
          <ToggleStatusAction
            resource="/employees"
            id={row.id}
            active={row.employment_status === "active"}
            refresh={refresh}
            field="employment_status"
          />
        </>
      )}
    />
  );
}
