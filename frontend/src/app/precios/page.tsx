import Link from "next/link";
import { CheckCircle2 } from "lucide-react";
import { MarketingLayout } from "@/components/marketing/marketing-layout";
import { Reveal } from "@/components/marketing/reveal";
import { SectionHeading } from "@/components/marketing/section-heading";

const plans = [
  { name: "Esencial", badge: "", features: ["Empleados", "Documentos", "Vacaciones", "Permisos"] },
  { name: "Profesional", badge: "Mas popular", features: ["Todo Esencial", "Asistencia", "Turnos", "Reportes"] },
  { name: "IA", badge: "Premium", features: ["Todo Profesional", "IA para RRHH", "WhatsApp", "Automatizaciones"] },
];

export default function PricingPage() {
  return (
    <MarketingLayout>
      <main className="bg-muted px-4 py-20 sm:px-6 lg:px-8">
        <Reveal><SectionHeading eyebrow="Precios" title="Planes preparados para crecer contigo" description="Precios configurables. La prioridad inicial es validar alcance, cantidad de empleados y modulos necesarios." /></Reveal>
        <div className="mx-auto mt-12 grid max-w-7xl gap-6 lg:grid-cols-3">
          {plans.map((plan) => (
            <Reveal key={plan.name}>
              <div className="relative h-full rounded-4xl border border-border bg-white p-8 shadow-sm">
                {plan.badge ? <span className="absolute right-6 top-6 rounded-full bg-primary px-3 py-1 text-xs font-semibold text-white">{plan.badge}</span> : null}
                <h2 className="text-2xl font-semibold text-navy">{plan.name}</h2>
                <p className="mt-3 text-sm text-muted-foreground">Precio a definir segun numero de empleados y alcance.</p>
                <div className="mt-8 space-y-3">
                  {plan.features.map((feature) => <p key={feature} className="flex items-center gap-3 text-sm text-navy"><CheckCircle2 className="h-5 w-5 text-success" /> {feature}</p>)}
                </div>
                <Link href="/demo" className="mt-8 inline-flex h-11 w-full items-center justify-center rounded-xl bg-primary text-sm font-semibold text-white">Solicitar demo</Link>
              </div>
            </Reveal>
          ))}
        </div>
      </main>
    </MarketingLayout>
  );
}
