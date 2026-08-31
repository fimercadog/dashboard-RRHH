import type * as React from "react";
import { formatDate } from "./utils";

export type AppColumnDef<T> = {
  id?: string;
  accessorKey?: keyof T | string;
  header?: React.ReactNode | ((context: unknown) => React.ReactNode);
  cell?: (context: { row: { original: T } }) => React.ReactNode;
};

/** Columna cuyo valor es una fecha ISO del backend y se muestra como `27/08/2026`. */
export function dateColumn<T>(accessorKey: string, header: React.ReactNode): AppColumnDef<T> {
  return {
    accessorKey,
    header,
    cell: ({ row }) => formatDate((row.original as Record<string, unknown>)[accessorKey] as string | null | undefined),
  };
}
