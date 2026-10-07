"use client";

import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { Deal } from "@/lib/types";

const STAGE_LABEL: Record<Deal["stage"], string> = {
  prospecting: "Prospección",
  qualification: "Calificación",
  proposal: "Propuesta",
  negotiation: "Negociación",
  won: "Ganado",
  lost: "Perdido",
  stalled: "Estancado",
};

const fmt = new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 });

const columns: AppColumnDef<Deal>[] = [
  { accessorKey: "title", header: "Titulo" },
  { header: "Cliente", cell: ({ row }) => row.original.client?.company_name ?? row.original.client?.first_name ?? "—" },
  { header: "Valor", cell: ({ row }) => fmt.format(row.original.amount) },
  { header: "Etapa", cell: ({ row }) => <Badge variant={badgeVariant(row.original.stage)}>{STAGE_LABEL[row.original.stage]}</Badge> },
  { header: "Cierre esperado", cell: ({ row }) => row.original.expected_close_date ? new Date(row.original.expected_close_date).toLocaleDateString("es-CO") : "—" },
  { header: "Responsable", cell: ({ row }) => row.original.owner?.name ?? "—" },
];

const fields: CrudField[] = [
  { name: "client_id", label: "Cliente", type: "relation-select", endpoint: "/clients?per_page=100", required: true },
  { name: "title", label: "Titulo", required: true },
  { name: "amount", label: "Valor (COP)", type: "number", required: true },
  {
    name: "stage", label: "Etapa", type: "select", required: true, defaultValue: "prospecting",
    options: [
      { label: "Prospección", value: "prospecting" },
      { label: "Calificación", value: "qualification" },
      { label: "Propuesta", value: "proposal" },
      { label: "Negociación", value: "negotiation" },
      { label: "Ganado", value: "won" },
      { label: "Perdido", value: "lost" },
      { label: "Estancado", value: "stalled" },
    ],
  },
  { name: "expected_close_date", label: "Fecha cierre esperada", type: "date" },
  { name: "notes", label: "Notas", type: "textarea", colSpan: "full" },
];

export default function DealsPage() {
  return (
    <ModuleTablePage<Deal>
      title="Oportunidades"
      description="Oportunidades comerciales del pipeline CRM."
      resource="/deals"
      exportResource="deals"
      columns={columns}
      fields={fields}
      actionLabel="Nueva oportunidad"
    />
  );
}
