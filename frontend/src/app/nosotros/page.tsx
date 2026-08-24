import { CTASection } from "@/components/marketing/cta-section";
import { MarketingLayout } from "@/components/marketing/marketing-layout";
import { Reveal } from "@/components/marketing/reveal";
import { SectionHeading } from "@/components/marketing/section-heading";

export default function AboutPage() {
  return (
    <MarketingLayout>
      <main>
        <section className="bg-white px-4 py-20 sm:px-6 lg:px-8">
          <Reveal><SectionHeading eyebrow="Nosotros" title="Creamos tecnologia accesible para simplificar Recursos Humanos" description="FidelOS HRMS nace para que pequenas y medianas empresas puedan digitalizar RRHH sin depender de procesos pesados, consultorias interminables o herramientas desconectadas." /></Reveal>
        </section>
        <section className="bg-muted px-4 py-20 sm:px-6 lg:px-8">
          <div className="mx-auto grid max-w-7xl gap-6 md:grid-cols-3">
            {["Simplificar RRHH", "Automatizar lo repetitivo", "Dar visibilidad a la empresa"].map((item) => (
              <Reveal key={item}>
                <div className="rounded-[2rem] bg-white p-8 shadow-sm">
                  <h2 className="text-xl font-semibold text-navy">{item}</h2>
                  <p className="mt-4 text-sm leading-6 text-muted-foreground">Diseñamos software claro, vendible y funcional para operar personas con menos friccion y mas trazabilidad.</p>
                </div>
              </Reveal>
            ))}
          </div>
        </section>
        <CTASection />
      </main>
    </MarketingLayout>
  );
}
