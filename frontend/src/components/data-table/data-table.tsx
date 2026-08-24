"use client";

import * as React from "react";
import {
  flexRender,
  getCoreRowModel,
  useReactTable,
} from "@tanstack/react-table";
import { Download, FileText, RefreshCw, Search } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { PaginatedResponse } from "@/lib/api";
import { AppColumnDef } from "@/lib/table-types";

type DataTableProps<TData> = {
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

export function DataTable<TData>({
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
  const table = useReactTable({
    data: data?.data ?? [],
    columns: columns as unknown as Parameters<typeof useReactTable>[0]["columns"],
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
  });

  const exportQuery = new URLSearchParams({ search }).toString();

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
              <Button variant="outline" size="sm" onClick={() => window.open(`${exportBaseUrl}.csv?${exportQuery}`, "_blank")}>
                <Download className="h-4 w-4" /> CSV
              </Button>
              <Button variant="outline" size="sm" onClick={() => window.open(`${exportBaseUrl}.pdf?${exportQuery}`, "_blank")}>
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
              {table.getHeaderGroups().map((headerGroup) => (
                <tr key={headerGroup.id}>
                  {headerGroup.headers.map((header) => (
                    <th key={header.id} className="px-4 py-3 font-medium">
                      {header.isPlaceholder ? null : flexRender(header.column.columnDef.header, header.getContext())}
                    </th>
                  ))}
                </tr>
              ))}
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
              ) : table.getRowModel().rows.length ? (
                table.getRowModel().rows.map((row) => (
                  <tr key={row.id} className="border-t border-border">
                    {row.getVisibleCells().map((cell) => (
                      <td key={cell.id} className="px-4 py-3 align-middle">
                        {flexRender(cell.column.columnDef.cell, cell.getContext())}
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
