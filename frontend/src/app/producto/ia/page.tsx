import type { Metadata } from "next";
import { ProductPage } from "@/components/marketing/product-page";
import { AIMockup } from "@/components/marketing/mockups";
import { productPages } from "@/components/marketing/marketing-data";

export const metadata: Metadata = {
  title: "IA para Recursos Humanos",
  description: "Inteligencia artificial aplicada a RRHH: análisis predictivo, asistente de consultas y automatización de procesos repetitivos.",
};

export default function AIProductPage() {
  const page = productPages.ia;
  return <ProductPage {...page}><AIMockup /></ProductPage>;
}
