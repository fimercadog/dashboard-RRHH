"use client";

import * as React from "react";
import { api, PaginatedResponse } from "@/lib/api";

export function useApiTable<T>(resource: string, extraParams?: Record<string, string>) {
  const [data, setData] = React.useState<PaginatedResponse<T>>();
  const [search, setSearch] = React.useState("");
  const [debouncedSearch, setDebouncedSearch] = React.useState("");
  const [page, setPage] = React.useState(1);
  const [perPage, setPerPage] = React.useState(10);
  const [refreshKey, setRefreshKey] = React.useState(0);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  React.useEffect(() => {
    const timer = setTimeout(() => {
      setPage(1);
      setDebouncedSearch(search);
    }, 300);
    return () => clearTimeout(timer);
  }, [search]);

  // Volver a página 1 cuando cambia el tamaño de página
  React.useEffect(() => { void (async () => { setPage(1); })(); }, [perPage]);

  React.useEffect(() => {
    const controller = new AbortController();
    queueMicrotask(() => setLoading(true));
    api
      .get<PaginatedResponse<T>>(resource, { params: { page, search: debouncedSearch, per_page: perPage, ...extraParams }, signal: controller.signal })
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
  }, [resource, page, perPage, debouncedSearch, refreshKey, JSON.stringify(extraParams)]);

  return { data, search, setSearch, page, setPage, perPage, setPerPage, loading, error, refresh: () => setRefreshKey((key) => key + 1) };
}
