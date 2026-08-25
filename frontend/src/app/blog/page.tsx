import Link from "next/link";
import { ArrowRight } from "lucide-react";
import { MarketingLayout } from "@/components/marketing/marketing-layout";
import { blogPosts } from "@/components/marketing/marketing-data";
import { Reveal } from "@/components/marketing/reveal";
import { SectionHeading } from "@/components/marketing/section-heading";

const categories = ["Recursos Humanos", "Productividad", "Reclutamiento", "Gestion de talento", "Asistencia", "Automatizacion", "IA"];

export default function BlogPage() {
  return (
    <MarketingLayout>
      <main className="px-4 py-20 sm:px-6 lg:px-8">
        <Reveal><SectionHeading eyebrow="Recursos" title="Ideas practicas para operar Recursos Humanos mejor" description="Articulos demo iniciales, listos para reemplazar por contenido editorial real." /></Reveal>
        <div className="mx-auto mt-8 flex max-w-7xl flex-wrap justify-center gap-2">
          {categories.map((category) => <span key={category} className="rounded-full bg-muted px-3 py-1 text-xs font-medium text-navy">{category}</span>)}
        </div>
        <div className="mx-auto mt-12 grid max-w-7xl gap-6 md:grid-cols-2 lg:grid-cols-3">
          {blogPosts.map((post) => (
            <Reveal key={post.slug}>
              <Link href={`/blog/${post.slug}`} className="block h-full rounded-4xl border border-border bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-(--marketing-shadow)">
                <p className="text-xs font-semibold uppercase tracking-[0.16em] text-primary">{post.category}</p>
                <h2 className="mt-4 text-xl font-semibold text-navy">{post.title}</h2>
                <p className="mt-3 text-sm leading-6 text-muted-foreground">{post.excerpt}</p>
                <span className="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-primary">Leer articulo <ArrowRight className="h-4 w-4" /></span>
              </Link>
            </Reveal>
          ))}
        </div>
      </main>
    </MarketingLayout>
  );
}
