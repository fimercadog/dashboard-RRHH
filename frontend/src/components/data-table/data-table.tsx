"use client";

import * as React from "react";
import {
  getCoreRowModel,
  useReactTable,
} from "@tanstack/react-table";
import { Download, FileText, RefreshCw, Search } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { api, PaginatedResponse } from "@/lib/api";
import { AppColumnDef } from "@/lib/table-types";

type DataTableProps<TData extends object> = {
  columns: AppColumnDef<TData>[];
  data?: PaginatedResponse<TData>;
  loading?: boolean;
  error?: string | null;
  search: string;
  onSearchChange: (value: string) => void;
  page: number;
  onPageChange: (page: number) => void;
  exportBaseUrl?: string;
};

export function DataTable<TData extends object>({
  columns,
  data,
  loading,
  error,
  search,
  onSearchChange,
  page,
  onPageChange,
  exportBaseUrl,
}: DataTableProps<TData>) {
  const table = useReactTable<TData>({
    data: data?.data ?? [],
    columns: columns as Parameters<typeof useReactTable<TData>>[0]["columns"],
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
  });

  const exportQuery = new URLSearchParams({ search }).toString();
  const rows = table.getRowModel().rows;

  async function downloadExport(format: "csv" | "pdf") {
    if (!exportBaseUrl) return;

    const response = await api.get(`${exportBaseUrl}.${format}?${exportQuery}`, { responseType: "blob" });
    const blobUrl = window.URL.createObjectURL(response.data);
    const link = document.createElement("a");

    link.href = blobUrl;
    link.download = `${exportBaseUrl.split("/").pop()}.${format}`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(blobUrl);
  }

  function columnKey(column: AppColumnDef<TData>, index: number) {
    return String(column.id ?? column.accessorKey ?? index);
  }

  function renderHeader(column: AppColumnDef<TData>): React.ReactNode {
    if (typeof column.header === "function") {
      return column.header({});
    }

    return column.header ?? (column.accessorKey ? String(column.accessorKey) : "");
  }

  function renderCell(column: AppColumnDef<TData>, row: TData): React.ReactNode {
    if (column.cell) {
      return column.cell({ row: { original: row } });
    }

    if (!column.accessorKey) {
      return null;
    }

    const value = row[column.accessorKey as keyof TData];
    return value == null ? "" : String(value);
  }

  return (
    <div className="space-y-4">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="relative max-w-sm">
          <Search className="pointer-events-none absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
          <Input className="pl-9" placeholder="Buscar..." value={search} onChange={(event) => onSearchChange(event.target.value)} />
        </div>
        <div className="flex gap-2">
          {exportBaseUrl ? (
            <>
              <Button variant="outline" size="sm" onClick={() => void downloadExport("csv")}>
                <Download className="h-4 w-4" /> CSV
              </Button>
              <Button variant="outline" size="sm" onClick={() => void downloadExport("pdf")}>
                <FileText className="h-4 w-4" /> PDF
              </Button>
            </>
          ) : null}
        </div>
      </div>

      <div className="overflow-hidden rounded-lg border border-border bg-card">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[760px] text-sm">
            <thead className="bg-muted text-left text-muted-foreground">
              <tr>
                {columns.map((column, index) => (
                  <th key={columnKey(column, index)} className="px-4 py-3 font-medium">
                    {renderHeader(column)}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td className="px-4 py-10 text-center text-muted-foreground" colSpan={columns.length}>
                    <RefreshCw className="mx-auto mb-2 h-5 w-5 animate-spin" /> Cargando datos...
                  </td>
                </tr>
              ) : error ? (
                <tr>
                  <td className="px-4 py-10 text-center text-destructive" colSpan={columns.length}>{error}</td>
                </tr>
              ) : rows.length ? (
                rows.map((row) => (
                  <tr key={row.id} className="border-t border-border">
                    {columns.map((column, index) => (
                      <td key={`${row.id}-${columnKey(column, index)}`} className="px-4 py-3 align-middle">
                        {renderCell(column, row.original)}
                      </td>
                    ))}
                  </tr>
                ))
              ) : (
                <tr>
                  <td className="px-4 py-10 text-center text-muted-foreground" colSpan={columns.length}>No hay registros para mostrar.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      <div className="flex items-center justify-between text-sm text-muted-foreground">
        <span>{data?.meta.total ?? 0} registros</span>
        <div className="flex items-center gap-2">
          <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => onPageChange(page - 1)}>Anterior</Button>
          <span>Pagina {data?.meta.current_page ?? page} de {data?.meta.last_page ?? 1}</span>
          <Button variant="outline" size="sm" disabled={!data || page >= data.meta.last_page} onClick={() => onPageChange(page + 1)}>Siguiente</Button>
        </div>
      </div>
    </div>
  );
}
