"use client";

import * as React from "react";
import { ModuleTablePage } from "@/components/module-table-page";
import { KpiCard } from "@/components/kpi-card";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { FinancialTransaction } from "@/lib/types";
import { api } from "@/lib/api";
import { TrendingUp, TrendingDown, BarChart, Calendar } from "lucide-react";

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

type FtStats = { total: number; income: number; expense: number; this_month: number };

export default function MovimientosFinanzasPage() {
  const [stats, setStats] = React.useState<FtStats | null>(null);
  const [statsStatus, setStatsStatus] = React.useState<"loading" | "error" | "ready">("loading");

  React.useEffect(() => {
    api.get<FtStats>("/financial-transactions/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
      <KpiCard label="Total movimientos" value={stats?.total ?? null} icon={BarChart} status={statsStatus} />
      <KpiCard label="Ingresos" value={stats?.income ?? null} icon={TrendingUp} status={statsStatus} />
      <KpiCard label="Egresos" value={stats?.expense ?? null} icon={TrendingDown} status={statsStatus} />
      <KpiCard label="Este mes" value={stats?.this_month ?? null} icon={Calendar} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage<FinancialTransaction>
      title="Movimientos financieros"
      description="Historial de ingresos, egresos y transferencias por cuenta."
      resource="/financial-transactions"
      exportResource="financial-transactions"
      columns={columns}
      fields={[]}
      statsSlot={statsSlot}
    />
  );
}
