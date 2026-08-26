import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
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
    default: "DFC Talento Humano | Software de Recursos Humanos para empresas",
    template: "%s | DFC Talento Humano",
  },
  description: "Centraliza empleados, asistencia, vacaciones, documentos, turnos, reclutamiento, reportes e IA para Recursos Humanos.",
  openGraph: {
    title: "DFC Talento Humano",
    description: "Software moderno de Recursos Humanos para empresas.",
    type: "website",
    locale: "es_CO",
    url: "/",
  },
  twitter: {
    card: "summary_large_image",
    title: "DFC Talento Humano",
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
        {children}
      </body>
    </html>
  );
}
