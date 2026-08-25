import Link from "next/link";
import { ArrowRight } from "lucide-react";
import { features } from "./marketing-data";
import { Reveal } from "./reveal";
import { SectionHeading } from "./section-heading";

export function FeatureGrid() {
  return (
    <section className="bg-white px-4 py-24 sm:px-6 lg:px-8">
      <Reveal>
        <SectionHeading
          eyebrow="Modulos conectados"
          title="Todo Recursos Humanos en una sola plataforma"
          description="Cada modulo resuelve una parte concreta de la operacion diaria, sin convertir RRHH en una coleccion de hojas de calculo."
        />
      </Reveal>
      <div className="mx-auto mt-12 grid max-w-7xl gap-5 sm:grid-cols-2 lg:grid-cols-4">
        {features.map((feature) => {
          const Icon = feature.icon;
          return (
            <Reveal key={feature.title}>
              <Link href={feature.href} className="group block h-full rounded-3xl border border-border bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-(--marketing-shadow)">
                <div className="mb-5 grid h-12 w-12 place-items-center rounded-2xl bg-accent text-primary">
                  <Icon className="h-6 w-6" />
                </div>
                <h3 className="text-lg font-semibold text-navy">{feature.title}</h3>
                <p className="mt-2 min-h-12 text-sm leading-6 text-muted-foreground">{feature.description}</p>
                <span className="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-primary">
                  Ver modulo <ArrowRight className="h-4 w-4 transition group-hover:translate-x-1" />
                </span>
              </Link>
            </Reveal>
          );
        })}
      </div>
    </section>
  );
}
