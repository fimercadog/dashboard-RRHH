"use client";

import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { STATUS_OPTIONS } from "@/lib/constants";
import { AppColumnDef } from "@/lib/table-types";
import { Category } from "@/lib/types";

const columns: AppColumnDef<Category>[] = [
  { accessorKey: "name", header: "Nombre" },
  { header: "Categoria padre", cell: ({ row }) => row.original.parent?.name ?? "—" },
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{row.original.status}</Badge> },
];

const fields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  { name: "parent_id", label: "Categoria padre", type: "relation-select", endpoint: "/categories?per_page=100" },
  {
    name: "status", label: "Estado", type: "select", required: true, defaultValue: "active",
    options: STATUS_OPTIONS,
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
