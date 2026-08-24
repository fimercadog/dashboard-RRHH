import { CTASection } from "@/components/marketing/cta-section";
import { DashboardPreview } from "@/components/marketing/dashboard-preview";
import { FeatureGrid } from "@/components/marketing/feature-grid";
import { MarketingLayout } from "@/components/marketing/marketing-layout";
import { ProblemSolution } from "@/components/marketing/problem-solution";
import { Reveal } from "@/components/marketing/reveal";
import { SectionHeading } from "@/components/marketing/section-heading";

export default function ProductOverviewPage() {
  return (
    <MarketingLayout>
      <main>
        <section className="bg-[linear-gradient(180deg,#ffffff_0%,#eef4ff_100%)] px-4 py-20 sm:px-6 lg:px-8">
          <div className="mx-auto grid max-w-7xl gap-12 lg:grid-cols-2 lg:items-center">
            <Reveal>
              <div>
                <p className="text-sm font-semibold uppercase tracking-[0.18em] text-primary">Producto</p>
                <h1 className="mt-4 text-5xl font-semibold tracking-tight text-navy">Software completo para administrar Recursos Humanos</h1>
                <p className="mt-5 text-lg leading-8 text-muted-foreground">Una plataforma SaaS para centralizar empleados, asistencia, vacaciones, permisos, documentos, turnos, reclutamiento, reportes e IA.</p>
              </div>
            </Reveal>
            <Reveal><DashboardPreview /></Reveal>
          </div>
        </section>
        <FeatureGrid />
        <ProblemSolution />
        <section className="px-4 py-20 sm:px-6 lg:px-8">
          <Reveal><SectionHeading eyebrow="Seguridad y automatizacion" title="Preparado para crecer con tu empresa" description="Base visual lista para conectarse a Laravel API, multiempresa, roles, permisos, auditoria e integraciones como WhatsApp." /></Reveal>
        </section>
        <CTASection />
      </main>
    </MarketingLayout>
  );
}
