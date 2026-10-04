"use client";

import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge } from "@/components/ui/badge";
import { STATUS_OPTIONS } from "@/lib/constants";
import { AppColumnDef } from "@/lib/table-types";
import { Unit } from "@/lib/types";

const columns: AppColumnDef<Unit>[] = [
  { accessorKey: "name", header: "Nombre" },
  { accessorKey: "abbreviation", header: "Abreviatura" },
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.status}</Badge> },
];

const fields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  { name: "abbreviation", label: "Abreviatura", required: true },
  {
    name: "status", label: "Estado", type: "select", required: true,
    options: STATUS_OPTIONS,
  },
];

export default function UnidadesPage() {
  return (
    <ModuleTablePage<Unit>
      title="Unidades de medida"
      description="Unidades para cuantificar productos del inventario."
      resource="/units"
      exportResource="units"
      columns={columns}
      fields={fields}
      actionLabel="Nueva unidad"
    />
  );
}
