import type { Metadata } from "next";
import { ProductPage } from "@/components/marketing/product-page";
import { EmployeeProfileMockup } from "@/components/marketing/mockups";
import { productPages } from "@/components/marketing/marketing-data";

export const metadata: Metadata = {
  title: "Gestión de empleados",
  description: "Centraliza toda la información de tus empleados: datos personales, contratos, historial y más desde un solo panel.",
};

export default function EmployeesProductPage() {
  const page = productPages.empleados;
  return <ProductPage {...page}><EmployeeProfileMockup /></ProductPage>;
}
