import { ContactForm } from "@/components/marketing/contact-form";
import { DashboardPreview } from "@/components/marketing/dashboard-preview";
import { FeatureGrid } from "@/components/marketing/feature-grid";
import { MarketingLayout } from "@/components/marketing/marketing-layout";
import { Reveal } from "@/components/marketing/reveal";

export default function DemoPage() {
  return (
    <MarketingLayout>
      <main>
        <section className="bg-[linear-gradient(180deg,#ffffff_0%,#eef4ff_100%)] px-4 py-20 sm:px-6 lg:px-8">
          <div className="mx-auto grid max-w-7xl gap-12 lg:grid-cols-2 lg:items-center">
            <Reveal>
              <div>
                <p className="text-sm font-semibold uppercase tracking-[0.18em] text-primary">Demo comercial</p>
                <h1 className="mt-4 text-5xl font-semibold tracking-tight text-navy">Mira como se veria Recursos Humanos operando desde una sola plataforma</h1>
                <p className="mt-5 text-lg leading-8 text-muted-foreground">Resolvemos dispersion de informacion, aprobaciones manuales, documentos sin control, asistencia poco visible y reclutamiento desordenado.</p>
              </div>
            </Reveal>
            <Reveal><DashboardPreview /></Reveal>
          </div>
        </section>
        <FeatureGrid />
        <section className="bg-muted px-4 py-20 sm:px-6 lg:px-8">
          <div className="mx-auto max-w-3xl"><ContactForm demo /></div>
        </section>
      </main>
    </MarketingLayout>
  );
}
