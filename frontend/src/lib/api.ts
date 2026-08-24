import axios from "axios";

export const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8001/api",
  headers: {
    Accept: "application/json",
  },
});

export type PaginatedResponse<T> = {
  data: T[];
  links: Record<string, string | null>;
  meta: {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
  };
};
