"use client";

import { Eye, UserPlus } from "lucide-react";
import Link from "next/link";
import { DataTable } from "@/components/data-table/data-table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { AppColumnDef } from "@/lib/table-types";
import { useApiTable } from "@/lib/use-api-table";
import { Employee } from "@/lib/types";

const columns: AppColumnDef<Employee>[] = [
  { accessorKey: "employee_code", header: "Codigo" },
  { header: "Nombre", cell: ({ row }) => row.original.full_name ?? `${row.original.first_name} ${row.original.last_name}` },
  { accessorKey: "email", header: "Correo" },
  { header: "Area", cell: ({ row }) => row.original.department?.name ?? "Sin area" },
  { header: "Cargo", cell: ({ row }) => row.original.position?.name ?? "Sin cargo" },
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.employment_status}</Badge> },
  {
    id: "actions",
    header: "",
    cell: ({ row }) => (
      <Link href={`/app/empleados/${row.original.id}`} className="inline-flex h-8 items-center gap-2 rounded-md px-2 hover:bg-muted">
        <Eye className="h-4 w-4" /> Ver
      </Link>
    ),
  },
];

export default function EmployeesPage() {
  const table = useApiTable<Employee>("/employees");

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Empleados</h1>
          <p className="text-sm text-muted-foreground">Listado central con busqueda, paginacion backend y exportaciones.</p>
        </div>
        <Button><UserPlus className="h-4 w-4" /> Nuevo empleado</Button>
      </div>
      <DataTable
        columns={columns}
        exportBaseUrl={`${process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8001/api"}/exports/employees`}
        data={table.data}
        search={table.search}
        onSearchChange={table.setSearch}
        page={table.page}
        onPageChange={table.setPage}
        loading={table.loading}
        error={table.error}
      />
    </div>
  );
}
