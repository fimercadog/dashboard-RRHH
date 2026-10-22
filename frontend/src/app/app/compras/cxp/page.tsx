"use client";

import { useEffect, useState } from "react";
import { AlertCircle, Clock, CreditCard } from "lucide-react";
import { KpiCard } from "@/components/kpi-card";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { api } from "@/lib/api";
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

type CxPStats = { total: number; open: number; overdue?: number };

export default function CxPPage() {
  const [stats, setStats] = useState<CxPStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<CxPStats>("/accounts-payable/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
      <KpiCard label="Total CxP" value={stats?.total ?? null} icon={CreditCard} status={statsStatus} />
      <KpiCard label="Pendientes pago" value={stats?.open ?? null} icon={Clock} status={statsStatus} />
      {stats?.overdue != null && (
        <KpiCard label="Vencidas" value={stats.overdue} icon={AlertCircle} status={statsStatus} />
      )}
    </div>
  );

  return (
    <ModuleTablePage<AccountPayable>
      title="Cuentas por pagar"
      description="Obligaciones pendientes con proveedores."
      resource="/accounts-payable"
      exportResource="accounts-payable"
      columns={columns}
      fields={[]}
      statsSlot={statsSlot}
    />
  );
}
