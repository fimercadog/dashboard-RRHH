"use client";

import { useEffect, useState } from "react";
import { AlertCircle, Clock, Receipt } from "lucide-react";
import { KpiCard } from "@/components/kpi-card";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { api } from "@/lib/api";
import { AppColumnDef } from "@/lib/table-types";
import { AccountsReceivable } from "@/lib/types";

const STATUS_LABELS: Record<string, string> = {
  open: "Abierta",
  partially_paid: "Pago parcial",
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

type CxCStats = { total: number; open: number; overdue?: number };

export default function CxCPage() {
  const [stats, setStats] = useState<CxCStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<CxCStats>("/accounts-receivable/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
      <KpiCard label="Total CxC" value={stats?.total ?? null} icon={Receipt} status={statsStatus} />
      <KpiCard label="Pendientes cobro" value={stats?.open ?? null} icon={Clock} status={statsStatus} />
      {stats?.overdue != null && (
        <KpiCard label="Vencidas" value={stats.overdue} icon={AlertCircle} status={statsStatus} />
      )}
    </div>
  );

  return (
    <ModuleTablePage<AccountsReceivable>
      title="Cuentas por cobrar"
      description="Saldos pendientes de cobro a clientes."
      resource="/accounts-receivable"
      exportResource="accounts-receivable"
      columns={columns}
      fields={[]}
      actionLabel=""
      statsSlot={statsSlot}
    />
  );
}
