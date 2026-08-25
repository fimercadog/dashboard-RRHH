import { Building2, CalendarClock, Users, Workflow } from "lucide-react";
import { CTASection } from "@/components/marketing/cta-section";
import { MarketingLayout } from "@/components/marketing/marketing-layout";
import { Reveal } from "@/components/marketing/reveal";
import { SectionHeading } from "@/components/marketing/section-heading";

const solutions = [
  { id: "pymes", title: "Para pequenas empresas", description: "Centraliza RRHH sin necesitar un departamento enorme.", icon: Building2 },
  { id: "rrhh", title: "Para equipos de Recursos Humanos", description: "Reduce trabajo operativo y gana trazabilidad.", icon: Users },
  { id: "turnos", title: "Para empresas con turnos", description: "Gestiona horarios, asistencia y descansos.", icon: CalendarClock },
  { id: "reclutamiento", title: "Para reclutamiento", description: "Organiza candidatos, vacantes y entrevistas.", icon: Workflow },
];

export default function SolutionsPage() {
  return (
    <MarketingLayout>
      <main>
        <section className="bg-muted px-4 py-20 sm:px-6 lg:px-8">
          <Reveal><SectionHeading eyebrow="Soluciones" title="Recursos Humanos ordenado para cada tipo de empresa" description="Bloques pensados para escenarios reales: equipos pequenos, RRHH operativo, turnos y seleccion de talento." /></Reveal>
        </section>
        <section className="px-4 py-20 sm:px-6 lg:px-8">
          <div className="mx-auto grid max-w-7xl gap-6 md:grid-cols-2">
            {solutions.map((solution) => {
              const Icon = solution.icon;
              return (
                <Reveal key={solution.id}>
                  <article id={solution.id} className="rounded-4xl border border-border bg-white p-8 shadow-sm">
                    <Icon className="mb-6 h-10 w-10 text-primary" />
                    <h2 className="text-2xl font-semibold text-navy">{solution.title}</h2>
                    <p className="mt-4 text-muted-foreground">{solution.description}</p>
                    <ul className="mt-6 space-y-3 text-sm text-muted-foreground">
                      <li>Procesos visibles y faciles de seguir.</li>
                      <li>Indicadores para decidir con datos.</li>
                      <li>Base preparada para permisos por rol.</li>
                    </ul>
                  </article>
                </Reveal>
              );
            })}
          </div>
        </section>
        <CTASection />
      </main>
    </MarketingLayout>
  );
}
