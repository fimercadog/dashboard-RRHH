"use client";

import { Eye } from "lucide-react";
import Link from "next/link";
import { CrudField } from "@/components/crud/crud-modal";
import { ToggleStatusAction } from "@/components/crud/toggle-status-action";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { Employee } from "@/lib/types";

const columns: AppColumnDef<Employee>[] = [
  { accessorKey: "employee_code", header: "Codigo" },
  { header: "Nombre", cell: ({ row }) => row.original.full_name ?? `${row.original.first_name} ${row.original.last_name}` },
  { accessorKey: "email", header: "Correo" },
  { header: "Area", cell: ({ row }) => row.original.department?.name ?? "Sin area" },
  { header: "Cargo", cell: ({ row }) => row.original.position?.name ?? "Sin cargo" },
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.employment_status}</Badge> },
];

const fields: CrudField[] = [
  { name: "employee_code", label: "Codigo", required: true },
  { name: "first_name", label: "Nombres", required: true },
  { name: "last_name", label: "Apellidos", required: true },
  { name: "identification_type", label: "Tipo de documento", required: true, placeholder: "CC" },
  { name: "identification_number", label: "Numero de documento", required: true },
  { name: "email", label: "Correo", type: "email" },
  { name: "hire_date", label: "Fecha de ingreso", type: "date", required: true },
  {
    name: "employment_status",
    label: "Estado",
    type: "select",
    required: true,
    options: [
      { label: "Activo", value: "active" },
      { label: "Inactivo", value: "inactive" },
      { label: "Terminado", value: "terminated" },
      { label: "En licencia", value: "on_leave" },
    ],
  },
  { name: "department_id", label: "ID area", type: "number" },
  { name: "position_id", label: "ID cargo", type: "number" },
  { name: "salary", label: "Salario", type: "number" },
];

export default function EmployeesPage() {
  return (
    <ModuleTablePage
      title="Empleados"
      description="Listado central con busqueda, paginacion backend y exportaciones."
      resource="/employees"
      exportResource="employees"
      columns={columns}
      fields={fields}
      actionLabel="Nuevo empleado"
      modalDescription="Captura la informacion basica del colaborador. Los catalogos por ID se refinan luego con selects conectados."
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
