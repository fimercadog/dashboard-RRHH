import { MarketingLayout } from "@/components/marketing/marketing-layout";
import { Reveal } from "@/components/marketing/reveal";

export type LegalSection = { heading: string; body: string[] };

export function LegalPage({
  title,
  updated,
  intro,
  sections,
}: {
  title: string;
  updated: string;
  intro?: string;
  sections: LegalSection[];
}) {
  return (
    <MarketingLayout>
      <main className="bg-white px-4 py-20 sm:px-6 lg:px-8">
        <Reveal>
          <article className="mx-auto max-w-3xl">
            <p className="mb-3 text-sm font-semibold uppercase tracking-[0.18em] text-primary">Legal</p>
            <h1 className="text-4xl font-semibold tracking-tight text-navy sm:text-5xl">{title}</h1>
            <p className="mt-3 text-sm text-muted-foreground">Ultima actualizacion: {updated}</p>
            {intro ? <p className="mt-6 text-base leading-7 text-muted-foreground">{intro}</p> : null}
            <div className="mt-10 space-y-10">
              {sections.map((section) => (
                <section key={section.heading}>
                  <h2 className="text-xl font-semibold text-navy">{section.heading}</h2>
                  <div className="mt-3 space-y-4 text-base leading-7 text-muted-foreground">
                    {section.body.map((paragraph, i) => (
                      <p key={i}>{paragraph}</p>
                    ))}
                  </div>
                </section>
              ))}
            </div>
          </article>
        </Reveal>
      </main>
    </MarketingLayout>
  );
}
