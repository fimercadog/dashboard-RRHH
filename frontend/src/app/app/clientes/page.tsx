"use client";

import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { Client } from "@/lib/types";

const columns: AppColumnDef<Client>[] = [
  { header: "Nombre / Empresa", cell: ({ row }) => row.original.company_name ?? row.original.full_name ?? `${row.original.first_name} ${row.original.last_name}` },
  { accessorKey: "identification_number", header: "Documento" },
  { accessorKey: "email", header: "Correo" },
  { accessorKey: "phone", header: "Telefono" },
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.status}</Badge> },
];

const fields: CrudField[] = [
  { name: "first_name", label: "Nombres", required: true },
  { name: "last_name", label: "Apellidos", required: true },
  {
    name: "identification_type", label: "Tipo de documento", type: "select",
    options: [
      { label: "CC", value: "CC" }, { label: "CE", value: "CE" },
      { label: "Pasaporte", value: "PA" }, { label: "NIT", value: "NIT" },
      { label: "PPT", value: "PPT" }, { label: "TI", value: "TI" },
    ],
  },
  { name: "company_name", label: "Nombre empresa", hint: "Si es persona juridica" },
  { name: "identification_number", label: "Numero de documento" },
  { name: "email", label: "Correo", type: "email" },
  { name: "phone", label: "Telefono" },
  { name: "city", label: "Ciudad" },
  { name: "address", label: "Direccion", type: "textarea", colSpan: "full" },
  { name: "notes", label: "Notas", type: "textarea", colSpan: "full" },
  {
    name: "status", label: "Estado", type: "select", required: true,
    options: [{ label: "Activo", value: "active" }, { label: "Inactivo", value: "inactive" }],
  },
];

export default function ClientesPage() {
  return (
    <ModuleTablePage
      title="Clientes"
      description="Clientes del ERP — personas naturales y empresas."
      resource="/clients"
      exportResource="clients"
      columns={columns}
      fields={fields}
      actionLabel="Nuevo cliente"
    />
  );
}
