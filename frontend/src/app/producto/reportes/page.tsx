import type { Metadata } from "next";
import { ProductPage } from "@/components/marketing/product-page";
import { ReportsMockup } from "@/components/marketing/mockups";
import { productPages } from "@/components/marketing/marketing-data";

export const metadata: Metadata = {
  title: "Reportes de RRHH",
  description: "Indicadores clave de tu fuerza laboral: asistencia, rotación, solicitudes y más. Exporta en PDF o Excel.",
};

export default function ReportsProductPage() {
  const page = productPages.reportes;
  return <ProductPage {...page}><ReportsMockup /></ProductPage>;
}
