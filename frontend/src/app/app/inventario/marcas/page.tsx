"use client";

import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { Brand } from "@/lib/types";

const columns: AppColumnDef<Brand>[] = [
  { accessorKey: "name", header: "Nombre" },
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.status}</Badge> },
];

const fields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  {
    name: "status", label: "Estado", type: "select", required: true,
    options: [{ label: "Activo", value: "active" }, { label: "Inactivo", value: "inactive" }],
  },
];

export default function MarcasPage() {
  return (
    <ModuleTablePage<Brand>
      title="Marcas"
      description="Marcas de productos del inventario."
      resource="/brands"
      exportResource="brands"
      columns={columns}
      fields={fields}
      actionLabel="Nueva marca"
    />
  );
}
