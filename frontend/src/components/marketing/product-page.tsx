import Link from "next/link";
import { ArrowRight, CheckCircle2 } from "lucide-react";
import { DashboardPreview } from "./dashboard-preview";
import { MarketingLayout } from "./marketing-layout";
import { Reveal } from "./reveal";
import { SectionHeading } from "./section-heading";

type ProductPageProps = {
  eyebrow: string;
  title: string;
  description: string;
  bullets: string[];
  children?: React.ReactNode;
};

export function ProductPage({ eyebrow, title, description, bullets, children }: ProductPageProps) {
  return (
    <MarketingLayout>
      <main>
        <section className="bg-[linear-gradient(180deg,#ffffff_0%,#fbe9f0_100%)] px-4 py-20 sm:px-6 lg:px-8">
          <div className="mx-auto grid max-w-7xl gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
            <Reveal>
              <div>
                <p className="text-sm font-semibold uppercase tracking-[0.18em] text-primary">{eyebrow}</p>
                <h1 className="mt-4 text-5xl font-semibold tracking-tight text-navy">{title}</h1>
                <p className="mt-5 text-lg leading-8 text-muted-foreground">{description}</p>
                <div className="mt-8 grid gap-3">
                  {bullets.map((bullet) => (
                    <div key={bullet} className="flex items-center gap-3 text-sm font-medium text-navy"><CheckCircle2 className="h-5 w-5 text-success" /> {bullet}</div>
                  ))}
                </div>
                <Link href="/demo" className="mt-8 inline-flex h-12 items-center gap-2 whitespace-nowrap rounded-xl bg-primary px-6 text-sm font-semibold text-white">
                  Solicitar demo <ArrowRight className="h-4 w-4" />
                </Link>
              </div>
            </Reveal>
            <Reveal>{children ?? <DashboardPreview />}</Reveal>
          </div>
        </section>
        <section className="px-4 py-20 sm:px-6 lg:px-8">
          <Reveal>
            <SectionHeading eyebrow="Beneficios" title="Preparado para operar hoy y escalar mañana" description="La arquitectura visual y funcional se piensa para API Laravel, multiempresa, roles, reportes e integraciones futuras." />
          </Reveal>
          <div className="mx-auto mt-12 grid max-w-7xl gap-5 md:grid-cols-3">
            {["Reduce trabajo manual", "Mejora trazabilidad", "Da visibilidad al negocio"].map((item) => (
              <div key={item} className="rounded-3xl border border-border bg-white p-6 shadow-sm">
                <h3 className="text-lg font-semibold text-navy">{item}</h3>
                <p className="mt-3 text-sm leading-6 text-muted-foreground">Una interfaz enfocada en decisiones concretas, estados claros y procesos que RRHH puede seguir sin perseguir informacion.</p>
              </div>
            ))}
          </div>
        </section>
      </main>
    </MarketingLayout>
  );
}
