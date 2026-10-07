"use client";

import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { AccountsReceivable } from "@/lib/types";

const STATUS_LABELS: Record<string, string> = {
  open: "Abierta",
  partially_paid: "Parcialmente pagada",
  paid: "Pagada",
  cancelled: "Cancelada",
};

const COP = (v: number) =>
  new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(v);

const columns: AppColumnDef<AccountsReceivable>[] = [
  { header: "Factura", cell: ({ row }) => row.original.sale_invoice?.number ?? "-" },
  { header: "Cliente", cell: ({ row }) => row.original.client?.name ?? "-" },
  { header: "Monto", cell: ({ row }) => COP(row.original.amount) },
  { header: "Saldo", cell: ({ row }) => COP(row.original.balance) },
  { accessorKey: "due_date", header: "Vence" },
  {
    header: "Estado",
    cell: ({ row }) => (
      <Badge variant={badgeVariant(row.original.status)}>{STATUS_LABELS[row.original.status] ?? row.original.status}</Badge>
    ),
  },
];

export default function CxCPage() {
  return (
    <ModuleTablePage<AccountsReceivable>
      title="Cuentas por cobrar"
      description="Saldos pendientes de cobro a clientes."
      resource="/accounts-receivable"
      columns={columns}
      fields={[]}
      actionLabel=""
    />
  );
}
