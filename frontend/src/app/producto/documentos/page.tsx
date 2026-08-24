import { ProductPage } from "@/components/marketing/product-page";
import { DocumentsMockup } from "@/components/marketing/mockups";
import { productPages } from "@/components/marketing/marketing-data";

export default function DocumentsProductPage() {
  const page = productPages.documentos;
  return <ProductPage {...page}><DocumentsMockup /></ProductPage>;
}
