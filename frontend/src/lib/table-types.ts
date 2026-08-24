import type * as React from "react";

export type AppColumnDef<T> = {
  id?: string;
  accessorKey?: keyof T | string;
  header?: React.ReactNode | ((context: unknown) => React.ReactNode);
  cell?: (context: { row: { original: T } }) => React.ReactNode;
};
