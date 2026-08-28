import { AIChatPreview } from "@/components/marketing/ai-chat-preview";
import { CTASection } from "@/components/marketing/cta-section";
import { FeatureGrid } from "@/components/marketing/feature-grid";
import { Hero } from "@/components/marketing/hero";
import { MarketingLayout } from "@/components/marketing/marketing-layout";
import { ProblemSolution } from "@/components/marketing/problem-solution";
import { Reveal } from "@/components/marketing/reveal";
import { SectionHeading } from "@/components/marketing/section-heading";

export default function Home() {
  return (
    <MarketingLayout>
      <main>
        <Hero />
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
