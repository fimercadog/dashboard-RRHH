import { BriefcaseBusiness } from "lucide-react";
import { CTASection } from "@/components/marketing/cta-section";
import { MarketingLayout } from "@/components/marketing/marketing-layout";
import { Reveal } from "@/components/marketing/reveal";
import { SectionHeading } from "@/components/marketing/section-heading";

const stages = ["Aplicaron", "Preseleccion", "Entrevista", "Oferta", "Contratado"];

export default function RecruitingPage() {
  return (
    <MarketingLayout>
      <main>
        <section className="bg-[linear-gradient(180deg,#ffffff_0%,#eef4ff_100%)] px-4 py-20 sm:px-6 lg:px-8">
          <Reveal><SectionHeading eyebrow="Reclutamiento" title="Pipeline visual para contratar sin perder candidatos" description="Reclutamiento es un modulo del sistema completo, no el producto entero. Organiza vacantes, candidatos, entrevistas y decisiones." /></Reveal>
          <div className="mx-auto mt-12 max-w-7xl rounded-[2rem] border border-border bg-white p-6 shadow-[var(--marketing-shadow)]">
            <div className="grid gap-4 lg:grid-cols-5">
              {stages.map((stage, index) => (
                <div key={stage} className="rounded-3xl bg-muted p-4">
                  <p className="mb-4 text-sm font-semibold text-navy">{stage}</p>
                  {[0, 1].map((card) => (
                    <div key={card} className="mb-3 rounded-2xl bg-white p-4 shadow-sm">
                      <BriefcaseBusiness className="mb-3 h-5 w-5 text-primary" />
                      <p className="text-sm font-semibold text-navy">{index === 0 ? "Desarrollador Backend" : "Analista RRHH"}</p>
                      <p className="text-xs text-muted-foreground">{27 - index * 4 - card} candidatos</p>
                    </div>
                  ))}
                </div>
              ))}
            </div>
          </div>
        </section>
        <CTASection />
      </main>
    </MarketingLayout>
  );
}
