import { ProductPage } from "@/components/marketing/product-page";
import { EmployeeProfileMockup } from "@/components/marketing/mockups";
import { productPages } from "@/components/marketing/marketing-data";

export default function EmployeesProductPage() {
  const page = productPages.empleados;
  return <ProductPage {...page}><EmployeeProfileMockup /></ProductPage>;
}
