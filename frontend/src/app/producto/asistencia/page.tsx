import type { Metadata } from "next";
import { ProductPage } from "@/components/marketing/product-page";
import { AttendanceMockup } from "@/components/marketing/mockups";
import { productPages } from "@/components/marketing/marketing-data";

export const metadata: Metadata = {
  title: "Control de asistencia",
  description: "Registra entradas, salidas y ausencias de tu equipo. Control de asistencia con estados, filtros y reportes en tiempo real.",
};

export default function AttendanceProductPage() {
  const page = productPages.asistencia;
  return <ProductPage {...page}><AttendanceMockup /></ProductPage>;
}
