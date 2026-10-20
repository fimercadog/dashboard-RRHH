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

const PAGE_SIZE_OPTIONS = [10, 25, 50, 100] as const;

type DataTableProps<TData extends object> = {
  columns: AppColumnDef<TData>[];
  data?: PaginatedResponse<TData>;
  loading?: boolean;
  error?: string | null;
  search: string;
  onSearchChange: (value: string) => void;
  page: number;
  onPageChange: (page: number) => void;
  perPage?: number;
  onPerPageChange?: (n: number) => void;
  exportBaseUrl?: string;
};

function getPageNumbers(current: number, last: number): (number | "…")[] {
  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
  const pages: (number | "…")[] = [1];
  const left = Math.max(2, current - 1);
  const right = Math.min(last - 1, current + 1);
  if (left > 2) pages.push("…");
  for (let i = left; i <= right; i++) pages.push(i);
  if (right < last - 1) pages.push("…");
  pages.push(last);
  return pages;
}

export function DataTable<TData extends object>({
  columns,
  data,
  loading,
  error,
  search,
  onSearchChange,
  page,
  onPageChange,
  perPage = 10,
  onPerPageChange,
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
  const offset = (page - 1) * (data?.meta?.per_page ?? 15);

  async function downloadExport(format: "csv" | "pdf") {
    if (!exportBaseUrl) return;
    try {
      const response = await api.get(`${exportBaseUrl}.${format}?${exportQuery}`, { responseType: "blob" });
      const blobUrl = window.URL.createObjectURL(response.data);
      const link = document.createElement("a");
      link.href = blobUrl;
      link.download = `${exportBaseUrl.split("/").pop()}.${format}`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      setTimeout(() => window.URL.revokeObjectURL(blobUrl), 1000);
    } catch {
      alert("No se pudo generar el archivo. Verifica tu conexión o permisos.");
    }
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

      <div className="overflow-x-auto rounded-lg bg-card">
        <table className="w-full min-w-190 text-sm">
          <thead className="bg-muted text-left text-muted-foreground">
            <tr>
              <th className="w-10 px-3 py-3 font-medium">N.º</th>
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
                <td className="px-4 py-10 text-center text-muted-foreground" colSpan={columns.length + 1}>
                  <RefreshCw className="mx-auto mb-2 h-5 w-5 animate-spin" /> Cargando datos...
                </td>
              </tr>
            ) : error ? (
              <tr>
                <td className="px-4 py-10 text-center text-destructive" colSpan={columns.length + 1}>{error}</td>
              </tr>
            ) : rows.length ? (
              rows.map((row, rowIndex) => (
                <tr key={row.id} className="border-t border-border odd:bg-background even:bg-muted">
                  <td className="px-3 py-3 align-middle text-xs tabular-nums text-muted-foreground">{offset + rowIndex + 1}</td>
                  {columns.map((column, index) => (
                    <td key={`${row.id}-${columnKey(column, index)}`} className="px-4 py-3 align-middle">
                      {renderCell(column, row.original)}
                    </td>
                  ))}
                </tr>
              ))
            ) : (
              <tr>
                <td className="px-4 py-10 text-center text-muted-foreground" colSpan={columns.length + 1}>No hay registros para mostrar.</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between text-sm text-muted-foreground">
        <div className="flex items-center gap-2">
          <span>{loading ? "Cargando…" : `${data?.meta?.total ?? 0} registros`}</span>
          {onPerPageChange ? (
            <label className="flex items-center gap-1.5">
              <span className="hidden sm:inline">Mostrar</span>
              <select
                className="rounded border border-border bg-card px-1.5 py-0.5 text-xs text-foreground"
                value={perPage}
                onChange={(e) => onPerPageChange(Number(e.target.value))}
              >
                {PAGE_SIZE_OPTIONS.map((n) => (
                  <option key={n} value={n}>{n}</option>
                ))}
              </select>
              <span className="hidden sm:inline">por página</span>
            </label>
          ) : null}
        </div>
        <div className="flex items-center gap-1 flex-wrap">
          <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => onPageChange(1)} title="Primera">«</Button>
          <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => onPageChange(page - 1)} title="Anterior">‹</Button>
          {getPageNumbers(data?.meta?.current_page ?? page, data?.meta?.last_page ?? 1).map((p, i) =>
            p === "…" ? (
              <span key={`ellipsis-${i}`} className="px-1">…</span>
            ) : (
              <Button
                key={p}
                variant={p === (data?.meta?.current_page ?? page) ? "default" : "outline"}
                size="sm"
                className="min-w-8"
                onClick={() => onPageChange(p)}
              >
                {p}
              </Button>
            )
          )}
          <Button variant="outline" size="sm" disabled={!data?.meta || page >= data.meta.last_page} onClick={() => onPageChange(page + 1)} title="Siguiente">›</Button>
          <Button variant="outline" size="sm" disabled={!data?.meta || page >= data.meta.last_page} onClick={() => onPageChange(data?.meta?.last_page ?? 1)} title="Última">»</Button>
        </div>
      </div>
    </div>
  );
}
