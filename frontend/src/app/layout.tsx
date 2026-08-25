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
  metadataBase: new URL("https://fidelos-hrms.local"),
  title: {
    default: "DAG Talento Humano | Software de Recursos Humanos para empresas",
    template: "%s | DAG Talento Humano",
  },
  description: "Centraliza empleados, asistencia, vacaciones, documentos, turnos, reclutamiento, reportes e IA para Recursos Humanos.",
  openGraph: {
    title: "DAG Talento Humano",
    description: "Software moderno de Recursos Humanos para empresas.",
    type: "website",
    locale: "es_CO",
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
