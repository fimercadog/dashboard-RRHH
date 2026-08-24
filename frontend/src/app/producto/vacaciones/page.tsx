import { ProductPage } from "@/components/marketing/product-page";
import { VacationFlowMockup } from "@/components/marketing/mockups";
import { productPages } from "@/components/marketing/marketing-data";

export default function VacationsProductPage() {
  const page = productPages.vacaciones;
  return <ProductPage {...page}><VacationFlowMockup /></ProductPage>;
}
