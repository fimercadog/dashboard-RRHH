import { ProductPage } from "@/components/marketing/product-page";
import { AttendanceMockup } from "@/components/marketing/mockups";
import { productPages } from "@/components/marketing/marketing-data";

export default function AttendanceProductPage() {
  const page = productPages.asistencia;
  return <ProductPage {...page}><AttendanceMockup /></ProductPage>;
}
