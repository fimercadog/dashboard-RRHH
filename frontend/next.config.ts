import type { NextConfig } from "next";

const isDev = process.env.NODE_ENV === "development";

const nextConfig: NextConfig = {
  devIndicators: false,
  allowedDevOrigins: ["*.ngrok-free.dev", "*.ngrok-free.app"],

  // El proxy /api -> Laravel local solo sirve en desarrollo. En produccion el
  // frontend habla directo con NEXT_PUBLIC_API_URL; dejar el rewrite activo
  // hacia 127.0.0.1:8001 (que no existe en el servidor) solo genera 500.
  async rewrites() {
    if (!isDev) return [];
    return [{ source: "/api/:path*", destination: "http://127.0.0.1:8001/api/:path*" }];
  },

  // El CDN de Hostinger (hcdn) cachea el HTML de las paginas prerenderizadas
  // con el `s-maxage=31536000` que emite Next, pero NO purga ese cache al
  // redeployar. Resultado: tras cada deploy sigue sirviendo HTML viejo que
  // apunta a chunks `_next/static/*` ya borrados -> 404 en JS/CSS -> pantalla
  // "This page couldn't load". Forzamos que el documento se revalide en cada
  // carga; los assets de `_next/static` llevan hash en el nombre y se
  // re-marcan como immutable (la ultima regla que coincide gana).
  async headers() {
    return [
      {
        source: "/:path*",
        headers: [{ key: "Cache-Control", value: "public, max-age=0, must-revalidate" }],
      },
      {
        source: "/_next/static/:path*",
        headers: [{ key: "Cache-Control", value: "public, max-age=31536000, immutable" }],
      },
    ];
  },
};

export default nextConfig;
