import type { Metadata } from "next";
import { ProductPage } from "@/components/marketing/product-page";
import { ShiftsMockup } from "@/components/marketing/mockups";
import { productPages } from "@/components/marketing/marketing-data";

export const metadata: Metadata = {
  title: "Gestión de turnos",
  description: "Planifica y asigna turnos de trabajo con visibilidad del equipo, alertas de conflictos y exportación de horarios.",
};

export default function ShiftsProductPage() {
  const page = productPages.turnos;
  return <ProductPage {...page}><ShiftsMockup /></ProductPage>;
}
