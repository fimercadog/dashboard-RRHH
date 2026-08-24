import { ProductPage } from "@/components/marketing/product-page";
import { ShiftsMockup } from "@/components/marketing/mockups";
import { productPages } from "@/components/marketing/marketing-data";

export default function ShiftsProductPage() {
  const page = productPages.turnos;
  return <ProductPage {...page}><ShiftsMockup /></ProductPage>;
}
