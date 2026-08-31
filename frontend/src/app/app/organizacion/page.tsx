"use client";

import { Badge } from "@/components/ui/badge";
import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { AppColumnDef } from "@/lib/table-types";

type Department = { id: number; name: string; description?: string; status: string };
type Position = { id: number; name: string; description?: string; status: string; department?: { id: number; name: string } | null };

const statusField: CrudField = {
  name: "status",
  label: "Estado",
  type: "select",
  required: true,
  options: [
    { label: "Activo", value: "active" },
    { label: "Inactivo", value: "inactive" },
  ],
};

const departmentColumns: AppColumnDef<Department>[] = [
  { accessorKey: "name", header: "Departamento" },
  { accessorKey: "description", header: "Descripcion" },
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.status}</Badge> },
];

const departmentFields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  { name: "description", label: "Descripcion", type: "textarea", colSpan: "full" },
  statusField,
];

const positionColumns: AppColumnDef<Position>[] = [
  { accessorKey: "name", header: "Cargo" },
  { header: "Departamento", cell: ({ row }) => row.original.department?.name ?? "Sin departamento" },
  { accessorKey: "description", header: "Descripcion" },
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.status}</Badge> },
];

const positionFields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  { name: "department_id", label: "ID departamento", type: "number" },
  { name: "description", label: "Descripcion", type: "textarea", colSpan: "full" },
  statusField,
];

export default function AppOrganizationPage() {
  return (
    <div className="space-y-12">
      <ModuleTablePage
        title="Departamentos"
        description="Areas de la organizacion."
        resource="/departments"
        columns={departmentColumns}
        fields={departmentFields}
        actionLabel="Nuevo departamento"
      />
      <ModuleTablePage
        title="Cargos"
        description="Cargos por departamento (usa el ID del departamento)."
        resource="/positions"
        columns={positionColumns}
        fields={positionFields}
        actionLabel="Nuevo cargo"
      />
    </div>
  );
}
