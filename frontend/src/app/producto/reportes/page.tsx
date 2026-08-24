import { ProductPage } from "@/components/marketing/product-page";
import { ReportsMockup } from "@/components/marketing/mockups";
import { productPages } from "@/components/marketing/marketing-data";

export default function ReportsProductPage() {
  const page = productPages.reportes;
  return <ProductPage {...page}><ReportsMockup /></ProductPage>;
}
