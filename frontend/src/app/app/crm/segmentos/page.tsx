"use client";

import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { AppColumnDef } from "@/lib/table-types";
import { Segment } from "@/lib/types";

const columns: AppColumnDef<Segment>[] = [
  { accessorKey: "name", header: "Nombre" },
  { header: "Descripcion", cell: ({ row }) => row.original.description ?? "—" },
];

const fields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  { name: "description", label: "Descripcion", type: "textarea", colSpan: "full" },
];

export default function SegmentosPage() {
  return (
    <ModuleTablePage<Segment>
      title="Segmentos"
      description="Segmentos para agrupar y clasificar clientes."
      resource="/segments"
      exportResource="segments"
      columns={columns}
      fields={fields}
      actionLabel="Nuevo segmento"
    />
  );
}
