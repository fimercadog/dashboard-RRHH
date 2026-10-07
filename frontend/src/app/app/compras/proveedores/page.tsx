"use client";

import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { STATUS_OPTIONS } from "@/lib/constants";
import { AppColumnDef } from "@/lib/table-types";
import { Supplier } from "@/lib/types";

const columns: AppColumnDef<Supplier>[] = [
  { accessorKey: "name", header: "Nombre" },
  { accessorKey: "nit", header: "NIT" },
  { accessorKey: "email", header: "Email" },
  { accessorKey: "phone", header: "Teléfono" },
  { accessorKey: "payment_terms", header: "Días de pago" },
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{row.original.status}</Badge> },
];

const fields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  { name: "nit", label: "NIT" },
  { name: "email", label: "Email", type: "email" },
  { name: "phone", label: "Teléfono" },
  { name: "address", label: "Dirección", type: "textarea" },
  { name: "payment_terms", label: "Días de pago (plazo)", type: "number" },
  { name: "status", label: "Estado", type: "select", required: true, options: STATUS_OPTIONS },
];

export default function ProveedoresPage() {
  return (
    <ModuleTablePage<Supplier>
      title="Proveedores"
      description="Directorio de proveedores de la empresa."
      resource="/suppliers"
      columns={columns}
      fields={fields}
      actionLabel="Nuevo proveedor"
    />
  );
}
