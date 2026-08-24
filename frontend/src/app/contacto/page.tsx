import { ContactForm } from "@/components/marketing/contact-form";
import { MarketingLayout } from "@/components/marketing/marketing-layout";
import { Reveal } from "@/components/marketing/reveal";
import { SectionHeading } from "@/components/marketing/section-heading";

export default function ContactPage() {
  return (
    <MarketingLayout>
      <main className="bg-muted px-4 py-20 sm:px-6 lg:px-8">
        <div className="mx-auto grid max-w-7xl gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
          <Reveal><SectionHeading align="left" eyebrow="Contacto" title="Hablemos de tu operacion de Recursos Humanos" description="Cuéntanos cuántas personas gestionas, qué procesos son más manuales y qué módulo quieres priorizar." /></Reveal>
          <Reveal><ContactForm /></Reveal>
        </div>
      </main>
    </MarketingLayout>
  );
}
