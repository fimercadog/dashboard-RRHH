import type { Metadata } from "next";
import { ProductPage } from "@/components/marketing/product-page";
import { VacationFlowMockup } from "@/components/marketing/mockups";
import { productPages } from "@/components/marketing/marketing-data";

export const metadata: Metadata = {
  title: "Gestión de vacaciones",
  description: "Solicitudes de vacaciones en línea con flujo de aprobación, saldos automáticos y visibilidad para todo el equipo.",
};

export default function VacationsProductPage() {
  const page = productPages.vacaciones;
  return <ProductPage {...page}><VacationFlowMockup /></ProductPage>;
}
