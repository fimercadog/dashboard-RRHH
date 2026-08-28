import Link from "next/link";
import { ArrowRight, CheckCircle2, PlayCircle } from "lucide-react";
import { AIChatPreview } from "@/components/marketing/ai-chat-preview";
import { CTASection } from "@/components/marketing/cta-section";
import { DashboardPreview } from "@/components/marketing/dashboard-preview";
import { FeatureGrid } from "@/components/marketing/feature-grid";
import { MarketingLayout } from "@/components/marketing/marketing-layout";
import { ProblemSolution } from "@/components/marketing/problem-solution";
import { Reveal } from "@/components/marketing/reveal";
import { SectionHeading } from "@/components/marketing/section-heading";
import { trustItems } from "@/components/marketing/marketing-data";

export default function Home() {
  return (
    <MarketingLayout>
      <main>
        <section className="relative overflow-hidden bg-[linear-gradient(180deg,#ffffff_0%,#fbe9f0_100%)] px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
          <div className="animate-marketing-float absolute left-1/2 top-16 h-72 w-72 rounded-full bg-primary/10 blur-3xl" />
          <div className="relative mx-auto grid max-w-7xl gap-12 lg:grid-cols-[1fr_0.95fr] lg:items-center">
            <Reveal>
              <div>
                <div className="mb-6 flex flex-wrap gap-2">
                  {["Facil de usar", "Seguro", "Para PYMES"].map((item, index) => (
                    <Reveal key={item} delay={index * 0.1} className="inline-flex">
                      <span className="inline-flex items-center gap-2 rounded-full border border-border bg-white px-3 py-1 text-xs font-medium text-navy">
                        <CheckCircle2 className="h-3.5 w-3.5 text-success" /> {item}
                      </span>
                    </Reveal>
                  ))}
                </div>
                <h1 className="max-w-3xl text-5xl font-semibold leading-[1.04] tracking-tight text-navy sm:text-6xl">
                  Gestiona tu equipo desde un solo lugar
                </h1>
                <p className="mt-6 max-w-2xl text-lg leading-8 text-muted-foreground">
                  Empleados, asistencia, documentos, vacaciones, permisos y reclutamiento en una sola plataforma.
                </p>
                <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                  <Link className="inline-flex h-12 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-primary px-6 text-sm font-semibold text-white shadow-lg shadow-primary/20 transition-transform duration-200 hover:scale-105 active:scale-95" href="/demo">
                    Solicitar demo <ArrowRight className="h-4 w-4" />
                  </Link>
                  <Link className="inline-flex h-12 items-center justify-center gap-2 rounded-xl border border-border bg-white px-6 text-sm font-semibold text-navy transition-transform duration-200 hover:scale-105 active:scale-95" href="/producto">
                    <PlayCircle className="h-4 w-4" /> Ver como funciona
                  </Link>
                </div>
                <div className="mt-10 grid max-w-lg grid-cols-3 gap-3">
                  {trustItems.map((item, index) => {
                    const Icon = item.icon;
                    return (
                      <Reveal key={item.label} delay={0.3 + index * 0.1}>
                        <div className="rounded-2xl bg-white p-4 text-sm font-medium text-navy shadow-sm transition-transform duration-200 hover:-translate-y-1"><Icon className="mb-2 h-5 w-5 text-primary" />{item.label}</div>
                      </Reveal>
                    );
                  })}
                </div>
              </div>
            </Reveal>
            <Reveal>
              <DashboardPreview />
            </Reveal>
          </div>
        </section>
        <FeatureGrid />
        <ProblemSolution />
        <section className="bg-white px-4 py-24 sm:px-6 lg:px-8">
          <div className="mx-auto grid max-w-7xl gap-12 lg:grid-cols-2 lg:items-center">
            <Reveal>
              <div>
                <SectionHeading align="left" eyebrow="IA premium" title="Tu asistente de Recursos Humanos disponible 24/7" description="Una experiencia conversacional para consultas frecuentes, solicitudes, politicas internas, certificados y vacaciones." />
                <div className="mt-8 grid gap-3 sm:grid-cols-2">
                  {["pedir certificado laboral", "consultar turnos", "consultar politicas", "generar solicitudes"].map((item) => (
                    <div key={item} className="rounded-2xl border border-border bg-white p-4 text-sm font-medium text-navy">{item}</div>
                  ))}
                </div>
              </div>
            </Reveal>
            <Reveal>
              <AIChatPreview />
            </Reveal>
          </div>
        </section>
        <CTASection />
      </main>
    </MarketingLayout>
  );
}
