"use client";

import * as React from "react";
import { Pencil, Plus } from "lucide-react";
import { CrudField, CrudModal } from "@/components/crud/crud-modal";
import { DataTable } from "@/components/data-table/data-table";
import { Button } from "@/components/ui/button";
import { AppColumnDef } from "@/lib/table-types";
import { useApiTable } from "@/lib/use-api-table";

type RowWithId = { id?: number | string };

type ModuleTablePageProps<T extends object & RowWithId> = {
  title: string;
  description: string;
  resource: string;
  exportResource?: string;
  columns: AppColumnDef<T>[];
  actionLabel?: string;
  fields?: CrudField[];
  modalDescription?: string;
  extraRowActions?: (row: T, refresh: () => void) => React.ReactNode;
};

export function ModuleTablePage<T extends object & RowWithId>({
  title,
  description,
  resource,
  exportResource,
  columns,
  actionLabel,
  fields,
  modalDescription,
  extraRowActions,
}: ModuleTablePageProps<T>) {
  const table = useApiTable<T>(resource);
  const [modalMode, setModalMode] = React.useState<"create" | "edit">("create");
  const [selectedRow, setSelectedRow] = React.useState<T | null>(null);
  const [modalOpen, setModalOpen] = React.useState(false);

  const openCreateModal = React.useCallback(() => {
    setModalMode("create");
    setSelectedRow(null);
    setModalOpen(true);
  }, []);

  const openEditModal = React.useCallback((row: T) => {
    setModalMode("edit");
    setSelectedRow(row);
    setModalOpen(true);
  }, []);

  const tableColumns = React.useMemo(() => {
    if (!fields?.length && !extraRowActions) return columns;

    return [
      ...columns,
      {
        id: "actions",
        header: "",
        cell: ({ row }: { row: { original: T } }) => (
          <div className="flex justify-end gap-1">
            {extraRowActions?.(row.original, table.refresh)}
            {fields?.length ? (
              <Button variant="ghost" size="sm" onClick={() => openEditModal(row.original)}>
                <Pencil className="h-4 w-4" /> Editar
              </Button>
            ) : null}
          </div>
        ),
      } satisfies AppColumnDef<T>,
    ];
  }, [columns, extraRowActions, fields, openEditModal, table.refresh]);

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">{title}</h1>
          <p className="text-sm text-muted-foreground">{description}</p>
        </div>
        {actionLabel && fields?.length ? (
          <Button onClick={openCreateModal}>
            <Plus className="h-4 w-4" /> {actionLabel}
          </Button>
        ) : actionLabel ? (
          <Button>{actionLabel}</Button>
        ) : null}
      </div>
      <DataTable
        columns={tableColumns}
        exportBaseUrl={exportResource ? `${process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8001/api"}/exports/${exportResource}` : undefined}
        data={table.data}
        search={table.search}
        onSearchChange={table.setSearch}
        page={table.page}
        onPageChange={table.setPage}
        loading={table.loading}
        error={table.error}
      />
      {fields?.length ? (
        <CrudModal
          open={modalOpen}
          onOpenChange={setModalOpen}
          mode={modalMode}
          title={modalMode === "edit" ? `Editar ${title.toLowerCase()}` : actionLabel ?? `Crear ${title.toLowerCase()}`}
          description={modalDescription}
          resource={resource}
          fields={fields}
          row={selectedRow}
          onSaved={table.refresh}
        />
      ) : null}
    </div>
  );
}
