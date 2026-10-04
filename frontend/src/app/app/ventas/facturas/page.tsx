"use client";

import { ModuleTablePage } from "@/components/module-table-page";
import { Badge } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { SaleInvoice } from "@/lib/types";

const STATUS_LABELS: Record<string, string> = {
  draft: "Borrador",
  posted: "Posteada",
  partially_paid: "Parcialmente pagada",
  paid: "Pagada",
  cancelled: "Cancelada",
};

const TYPE_LABELS: Record<string, string> = {
  invoice: "Factura",
  return: "Nota crédito",
};

const COP = (v: number) =>
  new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(v);

const columns: AppColumnDef<SaleInvoice>[] = [
  { accessorKey: "number", header: "Número" },
  { header: "Cliente", cell: ({ row }) => row.original.client?.name ?? "-" },
  { header: "Tipo", cell: ({ row }) => TYPE_LABELS[row.original.type] ?? row.original.type },
  { accessorKey: "date", header: "Fecha" },
  { accessorKey: "due_date", header: "Vence" },
  { header: "Total", cell: ({ row }) => COP(row.original.total) },
  {
    header: "Estado",
    cell: ({ row }) => (
      <Badge>{STATUS_LABELS[row.original.status] ?? row.original.status}</Badge>
    ),
  },
];

export default function FacturasVentaPage() {
  return (
    <ModuleTablePage<SaleInvoice>
      title="Facturas de venta"
      description="Facturas y notas crédito emitidas a clientes."
      resource="/sale-invoices"
      columns={columns}
      fields={[]}
      actionLabel="Nueva factura"
    />
  );
}
