import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

/**
 * ISO / Date -> `27/08/2026`. Devuelve el valor crudo si no es una fecha valida.
 * Los campos de fecha del backend llegan como medianoche UTC (`...T00:00:00Z`),
 * asi que se formatea en UTC para no correr el dia segun la zona del navegador.
 */
export function formatDate(value: string | number | Date | null | undefined): string {
  if (!value) return "";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return String(value);
  return date.toLocaleDateString("es-CO", { day: "2-digit", month: "2-digit", year: "numeric", timeZone: "UTC" });
}
