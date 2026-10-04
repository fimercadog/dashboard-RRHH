"use client";

import { ModuleTablePage } from "@/components/module-table-page";
import { Badge } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { PurchaseOrder } from "@/lib/types";

const STATUS_LABELS: Record<string, string> = {
  draft: "Borrador",
  sent: "Enviada",
  partial: "Parcial",
  received: "Recibida",
  cancelled: "Cancelada",
};


const columns: AppColumnDef<PurchaseOrder>[] = [
  { accessorKey: "number", header: "Número" },
  { header: "Proveedor", cell: ({ row }) => row.original.supplier?.name ?? "-" },
  { accessorKey: "date", header: "Fecha" },
  { accessorKey: "expected_date", header: "Fecha esperada" },
  {
    header: "Total",
    cell: ({ row }) =>
      new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(row.original.total),
  },
  {
    header: "Estado",
    cell: ({ row }) => (
      <Badge>{STATUS_LABELS[row.original.status] ?? row.original.status}</Badge>
    ),
  },
];

export default function OrdenesPage() {
  return (
    <ModuleTablePage<PurchaseOrder>
      title="Órdenes de compra"
      description="Órdenes de compra emitidas a proveedores."
      resource="/purchase-orders"
      columns={columns}
      fields={[]}
      actionLabel="Nueva orden"
    />
  );
}
