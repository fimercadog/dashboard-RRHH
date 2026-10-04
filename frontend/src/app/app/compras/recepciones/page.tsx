"use client";

import { ModuleTablePage } from "@/components/module-table-page";
import { Badge } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { PurchaseReceipt } from "@/lib/types";

const STATUS_LABELS: Record<string, string> = {
  draft: "Borrador",
  posted: "Posteada",
  cancelled: "Cancelada",
};

const columns: AppColumnDef<PurchaseReceipt>[] = [
  { accessorKey: "number", header: "Número" },
  { header: "Proveedor", cell: ({ row }) => row.original.supplier?.name ?? "-" },
  { header: "Bodega", cell: ({ row }) => row.original.warehouse?.name ?? "-" },
  { accessorKey: "date", header: "Fecha" },
  { accessorKey: "type", header: "Tipo" },
  {
    header: "Estado",
    cell: ({ row }) => (
      <Badge>{STATUS_LABELS[row.original.status] ?? row.original.status}</Badge>
    ),
  },
];

export default function RecepcionesPage() {
  return (
    <ModuleTablePage<PurchaseReceipt>
      title="Recepciones"
      description="Recepciones y devoluciones de mercancía."
      resource="/purchase-receipts"
      columns={columns}
      fields={[]}
      actionLabel="Nueva recepción"
    />
  );
}
