"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { api } from "@/lib/api";

export type PendingItem = {
  type: string;
  title: string;
  description: string;
  module: string;
  route: string;
  priority: "high" | "medium" | "low";
  date: string | null;
  count: number;
};

type PendingResult = {
  items: PendingItem[];
  total: number;
};

const POLL_MS = 60_000;

export function usePending() {
  const [data, setData] = useState<PendingResult>({ items: [], total: 0 });
  const [loading, setLoading] = useState(true);
  const timerRef = useRef<ReturnType<typeof setInterval> | null>(null);

  const fetch = useCallback(async () => {
    try {
      const res = await api.get<PendingResult>("/pending");
      setData(res.data);
    } catch {
      // Silently ignore — pending is non-critical; errors shouldn't crash the shell.
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void fetch();
    timerRef.current = setInterval(() => void fetch(), POLL_MS);
    return () => {
      if (timerRef.current) clearInterval(timerRef.current);
    };
  }, [fetch]);

  return { items: data.items, total: data.total, loading, refresh: fetch };
}
