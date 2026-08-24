"use client";

import { CrudField } from "@/components/crud/crud-modal";
import { ToggleStatusAction } from "@/components/crud/toggle-status-action";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { AppUser } from "@/lib/types";

const columns: AppColumnDef<AppUser>[] = [
  { accessorKey: "name", header: "Nombre" },
  { accessorKey: "email", header: "Correo" },
  { header: "Empleado", cell: ({ row }) => row.original.employee?.full_name ?? "Sin vincular" },
  { header: "Roles", cell: ({ row }) => row.original.roles?.join(", ") || "Sin rol" },
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.status}</Badge> },
];

const fields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  { name: "email", label: "Correo", type: "email", required: true },
  { name: "password", label: "Contrasena", type: "password", placeholder: "Dejar en blanco para generar una automatica", omitWhenEmpty: true },
  { name: "employee_id", label: "ID empleado", type: "number" },
  {
    name: "status",
    label: "Estado",
    type: "select",
    required: true,
    options: [
      { label: "Activo", value: "active" },
      { label: "Inactivo", value: "inactive" },
    ],
  },
];

export default function AppUsersPage() {
  return (
    <ModuleTablePage
      title="Usuarios"
      description="Cuentas de acceso al panel, vinculadas opcionalmente a un empleado."
      resource="/users"
      columns={columns}
      fields={fields}
      actionLabel="Nuevo usuario"
      modalDescription="Si dejas la contrasena en blanco se genera una temporal y se muestra al crear."
      extraRowActions={(row, refresh) => (
        <ToggleStatusAction resource="/users" id={row.id} active={row.status === "active"} refresh={refresh} />
      )}
    />
  );
}
