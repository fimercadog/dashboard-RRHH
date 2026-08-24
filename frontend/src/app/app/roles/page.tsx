"use client";

import { CrudField } from "@/components/crud/crud-modal";
import { ToggleStatusAction } from "@/components/crud/toggle-status-action";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { Role } from "@/lib/types";

const columns: AppColumnDef<Role>[] = [
  { accessorKey: "name", header: "Rol" },
  { header: "Permisos", cell: ({ row }) => row.original.permissions_count ?? 0 },
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.status}</Badge> },
];

const fields: CrudField[] = [
  { name: "name", label: "Nombre del rol", required: true },
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

export default function AppRolesPage() {
  return (
    <ModuleTablePage
      title="Roles y permisos"
      description="Arquitectura visual conectable a permisos backend."
      resource="/roles"
      columns={columns}
      fields={fields}
      actionLabel="Nuevo rol"
      modalDescription="Los permisos finos por recurso se asignan luego desde el detalle del rol."
      extraRowActions={(row, refresh) => (
        <ToggleStatusAction resource="/roles" id={row.id} active={row.status === "active"} refresh={refresh} />
      )}
    />
  );
}
