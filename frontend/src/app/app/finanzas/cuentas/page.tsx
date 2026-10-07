"use client";

import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { CashAccount } from "@/lib/types";

const COP = (v: number) =>
  new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(v);

const TYPE_LABELS: Record<string, string> = {
  cash: "Efectivo",
  bank: "Banco",
  savings: "Ahorros",
};

const columns: AppColumnDef<CashAccount>[] = [
  { accessorKey: "name", header: "Nombre" },
  { header: "Tipo", cell: ({ row }) => TYPE_LABELS[row.original.type] ?? row.original.type },
  { accessorKey: "currency", header: "Moneda" },
  {
    header: "Saldo",
    cell: ({ row }) => (
      <span className={row.original.balance < 0 ? "text-destructive font-semibold" : ""}>
        {COP(row.original.balance)}
      </span>
    ),
  },
  {
    header: "Estado",
    cell: ({ row }) => (
      <Badge variant={badgeVariant(row.original.status)}>
        {row.original.status === "active" ? "Activa" : "Inactiva"}
      </Badge>
    ),
  },
];

const fields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  {
    name: "type",
    label: "Tipo",
    type: "select",
    required: true,
    options: [
      { value: "cash", label: "Efectivo" },
      { value: "bank", label: "Banco" },
      { value: "savings", label: "Ahorros" },
    ],
  },
  { name: "currency", label: "Moneda (ej: COP)", required: false },
  { name: "notes", label: "Notas", type: "textarea" },
  {
    name: "status",
    label: "Estado",
    type: "select",
    options: [
      { value: "active", label: "Activa" },
      { value: "inactive", label: "Inactiva" },
    ],
  },
];

export default function CuentasPage() {
  return (
    <ModuleTablePage<CashAccount>
      title="Cuentas de efectivo"
      description="Cajas, cuentas bancarias y de ahorro de la empresa."
      resource="/cash-accounts"
      columns={columns}
      fields={fields}
      actionLabel="Nueva cuenta"
    />
  );
}
