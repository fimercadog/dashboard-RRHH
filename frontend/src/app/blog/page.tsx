import type { Metadata } from "next";
import Link from "next/link";
import { ArrowRight, Clock } from "lucide-react";
import { MarketingLayout } from "@/components/marketing/marketing-layout";
import { blogPosts } from "@/components/marketing/marketing-data";
import { Reveal } from "@/components/marketing/reveal";

export const metadata: Metadata = {
  title: "Recursos",
  description: "Articulos practicos sobre gestion de Recursos Humanos, asistencia, vacaciones, reclutamiento y automatizacion para equipos de talento.",
  openGraph: { title: "Recursos — FidelOS", description: "Articulos practicos sobre gestion de Recursos Humanos para equipos de talento." },
};

export default function BlogPage() {
  const [featured, ...rest] = blogPosts;
  return (
    <MarketingLayout>
      <main>
        {/* Hero */}
        <section className="bg-white px-4 py-20 sm:px-6 lg:px-8">
          <div className="mx-auto max-w-7xl">
            <Reveal>
              <p className="text-sm font-semibold uppercase tracking-[0.18em] text-primary">Recursos</p>
              <h1 className="mt-4 max-w-2xl text-5xl font-semibold tracking-tight text-navy">Ideas practicas para operar Recursos Humanos mejor</h1>
              <p className="mt-5 max-w-xl text-lg leading-8 text-muted-foreground">Articulos sobre gestion de equipos, asistencia, vacaciones, reclutamiento y automatizacion para lideres de talento.</p>
            </Reveal>
          </div>
        </section>

        {/* Featured */}
        <section className="bg-muted px-4 py-16 sm:px-6 lg:px-8">
          <div className="mx-auto max-w-7xl">
            <Reveal>
              <Link href={`/blog/${featured.slug}`} className="group flex flex-col gap-8 rounded-4xl border border-border bg-white p-8 shadow-sm transition hover:shadow-(--marketing-shadow) lg:flex-row lg:items-center">
                {/* decorative accent */}
                <div className="hidden shrink-0 lg:block lg:w-64 xl:w-80">
                  <div className="h-48 rounded-2xl bg-linear-to-br from-primary/20 to-primary/5 p-6">
                    <div className="h-full w-full rounded-xl bg-white/60" />
                  </div>
                </div>
                <div className="flex-1">
                  <p className="inline-block rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">{featured.category}</p>
                  <h2 className="mt-4 text-2xl font-semibold text-navy group-hover:text-primary transition-colors sm:text-3xl">{featured.title}</h2>
                  <p className="mt-3 text-base leading-7 text-muted-foreground">{featured.excerpt}</p>
                  <div className="mt-6 flex items-center gap-4">
                    {featured.readTime && (
                      <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <Clock className="h-3.5 w-3.5" />{featured.readTime}
                      </span>
                    )}
                    <span className="inline-flex items-center gap-2 text-sm font-semibold text-primary">Leer articulo <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" /></span>
                  </div>
                </div>
              </Link>
            </Reveal>
          </div>
        </section>

        {/* Grid */}
        <section className="bg-white px-4 py-16 sm:px-6 lg:px-8">
          <div className="mx-auto max-w-7xl">
            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
              {rest.map((post) => (
                <Reveal key={post.slug}>
                  <Link href={`/blog/${post.slug}`} className="group flex h-full flex-col rounded-4xl border border-border bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-(--marketing-shadow)">
                    <p className="inline-block w-fit rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">{post.category}</p>
                    <h2 className="mt-4 flex-1 text-xl font-semibold text-navy">{post.title}</h2>
                    <p className="mt-3 text-sm leading-6 text-muted-foreground">{post.excerpt}</p>
                    <div className="mt-6 flex items-center justify-between">
                      {post.readTime && (
                        <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
                          <Clock className="h-3.5 w-3.5" />{post.readTime}
                        </span>
                      )}
                      <span className="inline-flex items-center gap-2 text-sm font-semibold text-primary">Leer <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" /></span>
                    </div>
                  </Link>
                </Reveal>
              ))}
            </div>
          </div>
        </section>

        {/* CTA */}
        <section className="bg-muted px-4 py-16 sm:px-6 lg:px-8">
          <div className="mx-auto max-w-2xl text-center">
            <Reveal>
              <h2 className="text-2xl font-semibold text-navy">Tienes preguntas sobre RRHH?</h2>
              <p className="mt-3 text-muted-foreground">Nuestro equipo puede ayudarte a ordenar tus procesos de talento humano.</p>
              <Link href="/contacto" className="mt-6 inline-flex h-11 items-center justify-center rounded-xl bg-primary px-6 text-sm font-semibold text-white transition-transform hover:scale-105">Hablar con nosotros</Link>
            </Reveal>
          </div>
        </section>
      </main>
    </MarketingLayout>
  );
}
