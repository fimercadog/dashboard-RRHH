"use client";

import { ModuleTablePage } from "@/components/module-table-page";
import { Badge } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { PurchaseInvoice } from "@/lib/types";

const STATUS_LABELS: Record<string, string> = {
  draft: "Borrador",
  posted: "Posteada",
  partially_paid: "Pago parcial",
  paid: "Pagada",
  cancelled: "Cancelada",
};

const columns: AppColumnDef<PurchaseInvoice>[] = [
  { accessorKey: "number", header: "Número" },
  { header: "Proveedor", cell: ({ row }) => row.original.supplier?.name ?? "-" },
  { accessorKey: "date", header: "Fecha" },
  { accessorKey: "due_date", header: "Vencimiento" },
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

export default function FacturasCompraPage() {
  return (
    <ModuleTablePage<PurchaseInvoice>
      title="Facturas de compra"
      description="Facturas recibidas de proveedores."
      resource="/purchase-invoices"
      columns={columns}
      fields={[]}
      actionLabel="Nueva factura"
    />
  );
}
