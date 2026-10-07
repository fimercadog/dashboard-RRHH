"use client";

import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { AccountPayable } from "@/lib/types";

const STATUS_LABELS: Record<string, string> = {
  open: "Abierta",
  partially_paid: "Pago parcial",
  paid: "Pagada",
  cancelled: "Cancelada",
};

const columns: AppColumnDef<AccountPayable>[] = [
  { header: "Proveedor", cell: ({ row }) => row.original.supplier?.name ?? "-" },
  { header: "Factura", cell: ({ row }) => row.original.purchase_invoice?.number ?? "-" },
  { accessorKey: "due_date", header: "Vencimiento" },
  {
    header: "Monto",
    cell: ({ row }) =>
      new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(row.original.amount),
  },
  {
    header: "Saldo",
    cell: ({ row }) =>
      new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(row.original.balance),
  },
  {
    header: "Estado",
    cell: ({ row }) => (
      <Badge variant={badgeVariant(row.original.status)}>{STATUS_LABELS[row.original.status] ?? row.original.status}</Badge>
    ),
  },
];

export default function CxPPage() {
  return (
    <ModuleTablePage<AccountPayable>
      title="Cuentas por pagar"
      description="Obligaciones pendientes con proveedores."
      resource="/accounts-payable"
      columns={columns}
      fields={[]}
    />
  );
}
