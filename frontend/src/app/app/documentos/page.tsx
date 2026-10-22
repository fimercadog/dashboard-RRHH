"use client";

import { useEffect, useState } from "react";
import { AlertCircle, Clock, FileText } from "lucide-react";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { CrudField } from "@/components/crud/crud-modal";
import { KpiCard } from "@/components/kpi-card";
import { ModuleTablePage } from "@/components/module-table-page";
import { api } from "@/lib/api";
import { AppColumnDef, dateColumn } from "@/lib/table-types";
import { DocumentRow } from "@/lib/types";
import { statusLabel } from "@/lib/labels";

const columns: AppColumnDef<DocumentRow>[] = [
  { header: "Empleado", cell: ({ row }) => row.original.employee?.full_name ?? row.original.employee?.first_name ?? "Empleado" },
  { accessorKey: "document_type", header: "Tipo" },
  { accessorKey: "name", header: "Documento" },
  dateColumn<DocumentRow>("expiration_date", "Vence"),
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{statusLabel(row.original.status)}</Badge> },
];

const fields: CrudField[] = [
  { name: "employee_id", label: "Empleado", type: "relation-search", endpoint: "/employees/selector", required: true, editReadonly: true },
  {
    name: "document_type", label: "Tipo de documento", type: "select", required: true,
    options: [
      { label: "Contrato", value: "contrato" },
      { label: "Hoja de vida", value: "hoja_vida" },
      { label: "Diploma", value: "diploma" },
      { label: "Certificado", value: "certificado" },
      { label: "Soporte disciplinario", value: "soporte_disciplinario" },
      { label: "Otro", value: "otro" },
    ],
  },
  { name: "name", label: "Nombre del documento", required: true },
  { name: "file", label: "Archivo (PDF, imagen, Word)", type: "file", accept: ".pdf,.jpg,.jpeg,.png,.doc,.docx", required: true, createOnly: true, colSpan: "full" },
  { name: "issue_date", label: "Emision", type: "date" },
  { name: "expiration_date", label: "Vencimiento", type: "date" },
  {
    name: "status", label: "Estado", type: "select", required: true, defaultValue: "valid",
    options: [
      { label: "Valido", value: "valid" },
      { label: "Por vencer", value: "expiring" },
      { label: "Vencido", value: "expired" },
      { label: "Pendiente revision", value: "pending_review" },
    ],
  },
  { name: "notes", label: "Notas", type: "textarea", colSpan: "full" },
];

type DocumentStats = { total: number; expired: number; expiring_soon: number };

export default function DocumentsPage() {
  const [stats, setStats] = useState<DocumentStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<DocumentStats>("/employee-documents/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
      <KpiCard label="Total documentos" value={stats?.total ?? null} icon={FileText} status={statsStatus} />
      <KpiCard label="Vencidos" value={stats?.expired ?? null} icon={AlertCircle} status={(stats?.expired ?? 0) > 0 ? "error" : statsStatus} />
      <KpiCard label="Por vencer (30d)" value={stats?.expiring_soon ?? null} icon={Clock} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage
      title="Documentos"
      description="Documentos de empleados con deteccion de vencimientos proximos."
      resource="/employee-documents"
      exportResource="employee-documents"
      columns={columns}
      fields={fields}
      actionLabel="Agregar documento"
      statsSlot={statsSlot}
    />
  );
}
