"use client";

import { DataTable } from "@/components/data-table/data-table";
import { Button } from "@/components/ui/button";
import { AppColumnDef } from "@/lib/table-types";
import { useApiTable } from "@/lib/use-api-table";

type ModuleTablePageProps<T> = {
  title: string;
  description: string;
  resource: string;
  exportResource?: string;
  columns: AppColumnDef<T>[];
  actionLabel?: string;
};

export function ModuleTablePage<T>({ title, description, resource, exportResource, columns, actionLabel }: ModuleTablePageProps<T>) {
  const table = useApiTable<T>(resource);

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">{title}</h1>
          <p className="text-sm text-muted-foreground">{description}</p>
        </div>
        {actionLabel ? <Button>{actionLabel}</Button> : null}
      </div>
      <DataTable
        columns={columns}
        exportBaseUrl={exportResource ? `${process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8001/api"}/exports/${exportResource}` : undefined}
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
