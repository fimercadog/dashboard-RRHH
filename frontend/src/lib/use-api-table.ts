"use client";

import * as React from "react";
import { api, PaginatedResponse } from "@/lib/api";

export function useApiTable<T>(resource: string, extraParams?: Record<string, string>) {
  const [data, setData] = React.useState<PaginatedResponse<T>>();
  const [search, setSearch] = React.useState("");
  const [page, setPage] = React.useState(1);
  const [refreshKey, setRefreshKey] = React.useState(0);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  React.useEffect(() => {
    const controller = new AbortController();
    queueMicrotask(() => setLoading(true));
    api
      .get<PaginatedResponse<T>>(resource, { params: { page, search, per_page: 10, ...extraParams }, signal: controller.signal })
      .then((response) => {
        setData(response.data);
        setError(null);
      })
      .catch((apiError) => {
        if (apiError.name !== "CanceledError") {
          setError("No se pudo cargar la informacion del API.");
        }
      })
      .finally(() => setLoading(false));

    return () => controller.abort();
  // ponytail: extraParams is stable when passed as a literal object at call site (Agenda).
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [resource, page, search, refreshKey, JSON.stringify(extraParams)]);

  return { data, search, setSearch, page, setPage, loading, error, refresh: () => setRefreshKey((key) => key + 1) };
}
