"use client";

import { ModuleTablePage } from "@/components/module-table-page";
import { Badge } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { Quote } from "@/lib/types";

const STATUS_LABELS: Record<string, string> = {
  draft: "Borrador",
  sent: "Enviada",
  accepted: "Aceptada",
  expired: "Vencida",
  cancelled: "Cancelada",
};

const COP = (v: number) =>
  new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(v);

const columns: AppColumnDef<Quote>[] = [
  { accessorKey: "number", header: "Número" },
  { header: "Cliente", cell: ({ row }) => row.original.client?.name ?? "-" },
  { accessorKey: "date", header: "Fecha" },
  { accessorKey: "valid_until", header: "Válida hasta" },
  { header: "Total", cell: ({ row }) => COP(row.original.total) },
  {
    header: "Estado",
    cell: ({ row }) => (
      <Badge>{STATUS_LABELS[row.original.status] ?? row.original.status}</Badge>
    ),
  },
];

export default function CotizacionesPage() {
  return (
    <ModuleTablePage<Quote>
      title="Cotizaciones"
      description="Cotizaciones enviadas a clientes."
      resource="/quotes"
      columns={columns}
      fields={[]}
      actionLabel="Nueva cotización"
    />
  );
}
