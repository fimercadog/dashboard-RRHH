import { ProductPage } from "@/components/marketing/product-page";
import { AIMockup } from "@/components/marketing/mockups";
import { productPages } from "@/components/marketing/marketing-data";

export default function AIProductPage() {
  const page = productPages.ia;
  return <ProductPage {...page}><AIMockup /></ProductPage>;
}
