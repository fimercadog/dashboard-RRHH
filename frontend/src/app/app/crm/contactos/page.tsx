"use client";

import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { AppColumnDef } from "@/lib/table-types";
import { Contact } from "@/lib/types";

const columns: AppColumnDef<Contact>[] = [
  { accessorKey: "name", header: "Nombre" },
  { header: "Cliente", cell: ({ row }) => row.original.client?.company_name ?? row.original.client?.first_name ?? "—" },
  { header: "Cargo", cell: ({ row }) => row.original.job_title ?? "—" },
  { header: "Correo", cell: ({ row }) => row.original.email ?? "—" },
  { header: "Telefono", cell: ({ row }) => row.original.phone ?? "—" },
];

const fields: CrudField[] = [
  { name: "client_id", label: "ID Cliente", type: "number", required: true },
  { name: "name", label: "Nombre", required: true },
  { name: "job_title", label: "Cargo" },
  { name: "email", label: "Correo", type: "email" },
  { name: "phone", label: "Telefono" },
  { name: "notes", label: "Notas", type: "textarea", colSpan: "full" },
];

export default function ContactosPage() {
  return (
    <ModuleTablePage<Contact>
      title="Contactos"
      description="Personas de contacto asociadas a clientes."
      resource="/contacts"
      exportResource="contacts"
      columns={columns}
      fields={fields}
      actionLabel="Nuevo contacto"
    />
  );
}
