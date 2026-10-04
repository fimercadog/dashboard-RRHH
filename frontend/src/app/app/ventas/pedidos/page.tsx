"use client";

import { ModuleTablePage } from "@/components/module-table-page";
import { Badge } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { SaleOrder } from "@/lib/types";

const STATUS_LABELS: Record<string, string> = {
  draft: "Borrador",
  confirmed: "Confirmado",
  partial: "Parcial",
  fulfilled: "Entregado",
  cancelled: "Cancelado",
};

const COP = (v: number) =>
  new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(v);

const columns: AppColumnDef<SaleOrder>[] = [
  { accessorKey: "number", header: "Número" },
  { header: "Cliente", cell: ({ row }) => row.original.client?.name ?? "-" },
  { header: "Bodega", cell: ({ row }) => row.original.warehouse?.name ?? "-" },
  { accessorKey: "date", header: "Fecha" },
  { header: "Total", cell: ({ row }) => COP(row.original.total) },
  {
    header: "Estado",
    cell: ({ row }) => (
      <Badge>{STATUS_LABELS[row.original.status] ?? row.original.status}</Badge>
    ),
  },
];

export default function PedidosPage() {
  return (
    <ModuleTablePage<SaleOrder>
      title="Pedidos de venta"
      description="Pedidos confirmados por clientes."
      resource="/sale-orders"
      columns={columns}
      fields={[]}
      actionLabel="Nuevo pedido"
    />
  );
}
