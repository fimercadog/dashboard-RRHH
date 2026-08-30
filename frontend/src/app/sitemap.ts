import type { MetadataRoute } from "next";
import { blogPosts } from "@/components/marketing/marketing-data";

const baseUrl = "https://dfctalentohumano.fidelmercadotech.com";

export default function sitemap(): MetadataRoute.Sitemap {
  const staticRoutes = [
    "",
    "/producto",
    "/producto/empleados",
    "/producto/asistencia",
    "/producto/vacaciones",
    "/producto/documentos",
    "/producto/turnos",
    "/producto/reportes",
    "/producto/ia",
    "/soluciones",
    "/reclutamiento",
    "/precios",
    "/nosotros",
    "/blog",
    "/contacto",
    "/demo",
    "/login",
    "/privacidad",
    "/terminos",
  ];

  return [
    ...staticRoutes.map((route) => ({ url: `${baseUrl}${route}`, lastModified: new Date() })),
    ...blogPosts.map((post) => ({ url: `${baseUrl}/blog/${post.slug}`, lastModified: new Date() })),
  ];
}
