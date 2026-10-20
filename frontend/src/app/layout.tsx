import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import Script from "next/script";
import "./globals.css";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  metadataBase: new URL("https://dfctalentohumano.fidelmercadotech.com"),
  title: {
    default: "FidelOS | Software de Recursos Humanos para empresas",
    template: "%s | FidelOS",
  },
  description: "Centraliza empleados, asistencia, vacaciones, documentos, turnos, reclutamiento, reportes e IA para Recursos Humanos.",
  openGraph: {
    title: "FidelOS",
    description: "Software moderno de Recursos Humanos para empresas.",
    type: "website",
    locale: "es_CO",
    url: "/",
  },
  twitter: {
    card: "summary_large_image",
    title: "FidelOS",
    description: "Software moderno de Recursos Humanos para empresas.",
  },
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html
      lang="es"
      suppressHydrationWarning
      className={`${geistSans.variable} ${geistMono.variable} h-full antialiased`}
    >
      <body className="min-h-full" suppressHydrationWarning>
        {/* Anti-FOUC: beforeInteractive se inyecta en <head> antes del primer render */}
        <Script
          id="theme-init"
          strategy="beforeInteractive"
          dangerouslySetInnerHTML={{
            __html: `try{var t=localStorage.getItem('hrms_theme');if(t==='dark'||(t!=='light'&&window.matchMedia('(prefers-color-scheme:dark)').matches))document.documentElement.classList.add('dark')}catch(e){}`,
          }}
        />
        {children}
      </body>
    </html>
  );
}
