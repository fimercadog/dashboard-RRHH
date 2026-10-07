"use client";

import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { FinancialTransaction } from "@/lib/types";

const COP = (v: number) =>
  new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(v);

const TYPE_LABELS: Record<string, string> = {
  income:       "Ingreso",
  expense:      "Egreso",
  transfer_in:  "Transferencia entrada",
  transfer_out: "Transferencia salida",
};

const columns: AppColumnDef<FinancialTransaction>[] = [
  { accessorKey: "date", header: "Fecha" },
  { header: "Cuenta", cell: ({ row }) => row.original.cash_account?.name ?? "-" },
  {
    header: "Tipo",
    cell: ({ row }) => (
      <Badge variant={badgeVariant(row.original.type)}>
        {TYPE_LABELS[row.original.type] ?? row.original.type}
      </Badge>
    ),
  },
  {
    header: "Monto",
    cell: ({ row }) => (
      <span className={row.original.type === "expense" || row.original.type === "transfer_out" ? "text-destructive" : "text-green-600"}>
        {row.original.type === "expense" || row.original.type === "transfer_out" ? "-" : "+"}
        {COP(row.original.amount)}
      </span>
    ),
  },
  { accessorKey: "description", header: "Descripción" },
  { header: "Referencia", cell: ({ row }) => `${row.original.reference_type} #${row.original.reference_id}` },
];

export default function MovimientosFinanzasPage() {
  return (
    <ModuleTablePage<FinancialTransaction>
      title="Movimientos financieros"
      description="Historial de ingresos, egresos y transferencias por cuenta."
      resource="/financial-transactions"
      columns={columns}
      fields={[]}
    />
  );
}
