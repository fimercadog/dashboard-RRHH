"use client";

import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { Category } from "@/lib/types";

const columns: AppColumnDef<Category>[] = [
  { accessorKey: "name", header: "Nombre" },
  { header: "Categoria padre", cell: ({ row }) => row.original.parent?.name ?? "—" },
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.status}</Badge> },
];

const fields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  { name: "parent_id", label: "ID Categoria padre", type: "number" },
  {
    name: "status", label: "Estado", type: "select", required: true,
    options: [{ label: "Activo", value: "active" }, { label: "Inactivo", value: "inactive" }],
  },
];

export default function CategoriasPage() {
  return (
    <ModuleTablePage<Category>
      title="Categorias"
      description="Categorias y subcategorias para organizar productos."
      resource="/categories"
      exportResource="categories"
      columns={columns}
      fields={fields}
      actionLabel="Nueva categoria"
    />
  );
}
