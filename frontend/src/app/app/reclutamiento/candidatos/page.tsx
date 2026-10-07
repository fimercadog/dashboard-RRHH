"use client";

import { ModuleTablePage } from "@/components/module-table-page";
import { CrudField } from "@/components/crud/crud-modal";
import { AppColumnDef } from "@/lib/table-types";

type JobCandidate = { id: number; first_name: string; last_name: string; identification_number?: string; email?: string; phone?: string; };

const columns: AppColumnDef<JobCandidate>[] = [
  { header: "Nombre", cell: ({ row }) => `${row.original.first_name} ${row.original.last_name}` },
  { accessorKey: "identification_number", header: "Documento" },
  { accessorKey: "email", header: "Correo" },
  { accessorKey: "phone", header: "Telefono" },
];

const fields: CrudField[] = [
  { name: "first_name", label: "Nombres", required: true },
  { name: "last_name", label: "Apellidos", required: true },
  { name: "identification_number", label: "Numero de documento" },
  { name: "email", label: "Correo", type: "email" },
  { name: "phone", label: "Telefono" },
  { name: "resume_url", label: "URL hoja de vida" },
  { name: "observations", label: "Observaciones", type: "textarea" },
];

export default function CandidatosPage() {
  return (
    <ModuleTablePage title="Candidatos"
      description="Registro de personas en proceso de seleccion."
      resource="/job-candidates" columns={columns} fields={fields}
      actionLabel="Nuevo candidato" modalDescription="Registra los datos del candidato." />
  );
}
