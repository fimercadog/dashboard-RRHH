import type { Metadata } from "next";
import { ProductPage } from "@/components/marketing/product-page";
import { DocumentsMockup } from "@/components/marketing/mockups";
import { productPages } from "@/components/marketing/marketing-data";

export const metadata: Metadata = {
  title: "Documentos laborales",
  description: "Almacena y organiza contratos, certificados y documentos laborales con control de vencimientos y acceso seguro.",
};

export default function DocumentsProductPage() {
  const page = productPages.documentos;
  return <ProductPage {...page}><DocumentsMockup /></ProductPage>;
}
