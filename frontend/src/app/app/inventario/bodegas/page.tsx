"use client";

import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge } from "@/components/ui/badge";
import { STATUS_OPTIONS } from "@/lib/constants";
import { AppColumnDef } from "@/lib/table-types";
import { Warehouse } from "@/lib/types";

const columns: AppColumnDef<Warehouse>[] = [
  { accessorKey: "name", header: "Nombre" },
  { header: "Ubicacion", cell: ({ row }) => row.original.location ?? "—" },
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.status}</Badge> },
];

const fields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  { name: "location", label: "Ubicacion" },
  {
    name: "status", label: "Estado", type: "select", required: true,
    options: STATUS_OPTIONS,
  },
];

export default function BodegasPage() {
  return (
    <ModuleTablePage<Warehouse>
      title="Bodegas"
      description="Almacenes y ubicaciones fisicas del inventario."
      resource="/warehouses"
      exportResource="warehouses"
      columns={columns}
      fields={fields}
      actionLabel="Nueva bodega"
    />
  );
}
