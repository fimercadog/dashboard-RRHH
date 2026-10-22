import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { ArrowLeft, ArrowRight, Clock, CalendarDays } from "lucide-react";
import { MarketingLayout } from "@/components/marketing/marketing-layout";
import { blogPosts } from "@/components/marketing/marketing-data";

type BlogSection =
  | { type: "p"; text: string }
  | { type: "h2"; text: string }
  | { type: "ul"; items: string[] }
  | { type: "cta"; text: string; href: string; label: string };

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }): Promise<Metadata> {
  const { slug } = await params;
  const post = blogPosts.find((item) => item.slug === slug);
  if (!post) return {};
  return {
    title: post.title,
    description: post.excerpt,
    openGraph: { title: post.title, description: post.excerpt, type: "article" },
  };
}

export function generateStaticParams() {
  return blogPosts.map((post) => ({ slug: post.slug }));
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString("es-CO", { year: "numeric", month: "long", day: "numeric" });
}

// Ilustracion SVG generativa basada en categoria
function ArticleIllustration({ category }: { category: string }) {
  const isAI = category.toLowerCase().includes("ia") || category.toLowerCase().includes("automatiz");
  const isRecruit = category.toLowerCase().includes("reclutamiento");
  const isAttend = category.toLowerCase().includes("asistencia");

  if (isAI) return (
    <svg viewBox="0 0 480 220" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-full h-full">
      <rect width="480" height="220" fill="url(#g1)" rx="16" />
      <defs>
        <linearGradient id="g1" x1="0" y1="0" x2="480" y2="220" gradientUnits="userSpaceOnUse">
          <stop stopColor="#0f1012" /><stop offset="1" stopColor="#14532d" />
        </linearGradient>
      </defs>
      {/* Nodos de red neuronal */}
      {[60,160,260,360].map((x, i) => (
        <circle key={i} cx={x} cy={80 + (i % 2) * 60} r="18" fill="#15803d" fillOpacity="0.3" stroke="#4ade80" strokeWidth="1.5" />
      ))}
      {[110,210,310].map((x, i) => (
        <circle key={i} cx={x} cy={140 + (i % 2) * -40} r="14" fill="#15803d" fillOpacity="0.2" stroke="#4ade80" strokeWidth="1" />
      ))}
      <line x1="60" y1="80" x2="110" y2="140" stroke="#4ade80" strokeOpacity="0.4" strokeWidth="1" />
      <line x1="60" y1="80" x2="110" y2="100" stroke="#4ade80" strokeOpacity="0.4" strokeWidth="1" />
      <line x1="160" y1="140" x2="210" y2="100" stroke="#4ade80" strokeOpacity="0.4" strokeWidth="1" />
      <line x1="260" y1="80" x2="310" y2="140" stroke="#4ade80" strokeOpacity="0.4" strokeWidth="1" />
      <line x1="360" y1="140" x2="310" y2="100" stroke="#4ade80" strokeOpacity="0.4" strokeWidth="1" />
      <circle cx="240" cy="110" r="28" fill="#15803d" fillOpacity="0.4" stroke="#4ade80" strokeWidth="2" />
      <text x="240" y="115" textAnchor="middle" fill="#4ade80" fontSize="11" fontFamily="monospace">IA</text>
      {/* Puntos decorativos */}
      {[40,80,120,160,200,240,280,320,360,400,440].map((x, i) => (
        <circle key={i} cx={x} cy={195} r="2" fill="#4ade80" fillOpacity="0.3" />
      ))}
    </svg>
  );

  if (isRecruit) return (
    <svg viewBox="0 0 480 220" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-full h-full">
      <rect width="480" height="220" fill="url(#g2)" rx="16" />
      <defs>
        <linearGradient id="g2" x1="0" y1="0" x2="480" y2="220" gradientUnits="userSpaceOnUse">
          <stop stopColor="#0f1012" /><stop offset="1" stopColor="#1e3a5f" />
        </linearGradient>
      </defs>
      {/* Pipeline de candidatos */}
      {[["Aplicacion","80"],["Revision","180"],["Entrevista","280"],["Oferta","380"]].map(([label, x], i) => (
        <g key={i}>
          <rect x={Number(x)-35} y="70" width="70" height="80" rx="10" fill="#15803d" fillOpacity={0.15 + i * 0.08} stroke="#4ade80" strokeWidth="1" />
          <circle cx={Number(x)} cy="100" r="18" fill="#15803d" fillOpacity="0.4" />
          <text x={Number(x)} y="105" textAnchor="middle" fill="#4ade80" fontSize="18">✓</text>
          <text x={Number(x)} y="165" textAnchor="middle" fill="#9ca3af" fontSize="9" fontFamily="sans-serif">{label}</text>
          {i < 3 && <line x1={Number(x)+35} y1="110" x2={Number(x)+65} y2="110" stroke="#4ade80" strokeOpacity="0.5" strokeWidth="1.5" strokeDasharray="4 2" />}
        </g>
      ))}
    </svg>
  );

  if (isAttend) return (
    <svg viewBox="0 0 480 220" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-full h-full">
      <rect width="480" height="220" fill="url(#g3)" rx="16" />
      <defs>
        <linearGradient id="g3" x1="0" y1="0" x2="480" y2="220" gradientUnits="userSpaceOnUse">
          <stop stopColor="#0f1012" /><stop offset="1" stopColor="#1a1a2e" />
        </linearGradient>
      </defs>
      {/* Grafica de barras de asistencia */}
      {[90,120,70,140,100,160,80,130,110,150,95,125].map((h, i) => (
        <rect key={i} x={40 + i * 34} y={180 - h} width="22" height={h} rx="4"
          fill="#15803d" fillOpacity={0.3 + (i % 3) * 0.15} />
      ))}
      <line x1="30" y1="180" x2="450" y2="180" stroke="#4ade80" strokeOpacity="0.3" strokeWidth="1" />
      <text x="240" y="40" textAnchor="middle" fill="#4ade80" fontSize="12" fontFamily="sans-serif" opacity="0.7">Asistencia mensual</text>
    </svg>
  );

  // Default: dashboard RRHH
  return (
    <svg viewBox="0 0 480 220" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-full h-full">
      <rect width="480" height="220" fill="url(#g4)" rx="16" />
      <defs>
        <linearGradient id="g4" x1="0" y1="0" x2="480" y2="220" gradientUnits="userSpaceOnUse">
          <stop stopColor="#0f1012" /><stop offset="1" stopColor="#0d2d1a" />
        </linearGradient>
      </defs>
      {/* Tarjetas de KPI */}
      {[["Empleados","48","↑3%",60],["Asistencia","94%","↑1%",180],["Vacaciones","12","→0%",300],["Docs","36","↓2",420]].map(([label,val,change,x],i) => (
        <g key={i}>
          <rect x={Number(x)-45} y="50" width="90" height="70" rx="10" fill="#15803d" fillOpacity={0.12 + i * 0.04} stroke="#4ade80" strokeWidth="0.8" />
          <text x={Number(x)} y="80" textAnchor="middle" fill="#f9fafb" fontSize="20" fontWeight="bold" fontFamily="sans-serif">{val}</text>
          <text x={Number(x)} y="98" textAnchor="middle" fill="#9ca3af" fontSize="8" fontFamily="sans-serif">{label}</text>
          <text x={Number(x)} y="112" textAnchor="middle" fill="#4ade80" fontSize="8" fontFamily="sans-serif">{change}</text>
        </g>
      ))}
      {/* Linea de tendencia */}
      <polyline points="30,170 90,155 150,165 210,148 270,158 330,142 390,150 450,138"
        fill="none" stroke="#4ade80" strokeWidth="2" strokeOpacity="0.5" />
      <polyline points="30,170 90,155 150,165 210,148 270,158 330,142 390,150 450,138 450,190 30,190"
        fill="#4ade80" fillOpacity="0.05" />
    </svg>
  );
}

// Segunda imagen: insight visual contextual
function InsightBlock({ category }: { category: string }) {
  const isTalent = category.toLowerCase().includes("talent") || category.toLowerCase().includes("document");
  const bgClass = isTalent ? "from-navy to-[#1a2f4a]" : "from-navy to-[#0d2d1a]";

  return (
    <div className={`relative overflow-hidden rounded-2xl bg-linear-to-br ${bgClass} p-8 my-10`}>
      <div className="absolute inset-0 opacity-10">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <pattern id="dots" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
              <circle cx="2" cy="2" r="1.5" fill="#4ade80" />
            </pattern>
          </defs>
          <rect width="100%" height="100%" fill="url(#dots)" />
        </svg>
      </div>
      <div className="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center">
        <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary/30 text-3xl">
          {isTalent ? "📁" : "📊"}
        </div>
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.16em] text-green-400">Punto clave</p>
          <p className="mt-1 text-base font-medium leading-relaxed text-white">
            {isTalent
              ? "Los documentos vencidos no son un problema de almacenamiento. Son un problema de visibilidad. Un sistema que alerta antes de que venzan convierte la reaccion en prevencion."
              : "El software no reemplaza los procesos: los hace visibles. Antes de digitalizar, define quien aprueba que, en cuanto tiempo y con que evidencia."}
          </p>
        </div>
      </div>
    </div>
  );
}

export default async function BlogPostPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const post = blogPosts.find((item) => item.slug === slug);
  if (!post) notFound();

  const body = (post as unknown as { body?: BlogSection[] }).body ?? [];
  const publishedAt = (post as unknown as { publishedAt?: string }).publishedAt;
  const readTime = (post as unknown as { readTime?: string }).readTime;

  // Dividir el body: primero 3 secciones, imagen, resto
  const firstHalf = body.slice(0, 3);
  const secondHalf = body.slice(3);

  return (
    <MarketingLayout>
      {/* Hero del articulo */}
      <div className="bg-navy px-4 pb-0 pt-16 sm:px-6 lg:px-8">
        <div className="mx-auto max-w-3xl">
          {/* Breadcrumb */}
          <Link href="/blog" className="inline-flex items-center gap-2 text-sm font-medium text-green-400/80 hover:text-green-400 transition-colors">
            <ArrowLeft className="h-4 w-4" /> Volver al blog
          </Link>

          {/* Meta */}
          <div className="mt-6 flex flex-wrap items-center gap-3">
            <span className="rounded-full bg-primary/20 px-3 py-1 text-xs font-semibold text-green-400 border border-green-400/20">
              {post.category}
            </span>
            {publishedAt && (
              <span className="flex items-center gap-1.5 text-xs text-gray-400">
                <CalendarDays className="h-3.5 w-3.5" />{formatDate(publishedAt)}
              </span>
            )}
            {readTime && (
              <span className="flex items-center gap-1.5 text-xs text-gray-400">
                <Clock className="h-3.5 w-3.5" />{readTime}
              </span>
            )}
          </div>

          {/* Titulo */}
          <h1 className="mt-5 text-3xl font-semibold leading-tight tracking-tight text-white sm:text-4xl lg:text-5xl">
            {post.title}
          </h1>

          {/* Excerpt */}
          <p className="mt-5 text-lg leading-8 text-gray-300">{post.excerpt}</p>
        </div>

        {/* Imagen hero — ilustracion SVG */}
        <div className="mx-auto mt-10 max-w-3xl overflow-hidden rounded-t-2xl">
          <ArticleIllustration category={post.category} />
        </div>
      </div>

      {/* Cuerpo del articulo */}
      <article className="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        {/* Primera mitad del body */}
        {firstHalf.length > 0 && (
          <div className="space-y-6">
            {firstHalf.map((section, i) => {
              if (section.type === "p") return (
                <p key={i} className="text-base leading-8 text-muted-foreground">{section.text}</p>
              );
              if (section.type === "h2") return (
                <h2 key={i} className="pt-4 text-2xl font-semibold text-navy">{section.text}</h2>
              );
              if (section.type === "ul") return (
                <ul key={i} className="list-disc space-y-2 pl-6 text-muted-foreground">
                  {section.items.map((item, j) => <li key={j}>{item}</li>)}
                </ul>
              );
              if (section.type === "cta") return (
                <div key={i} className="rounded-2xl bg-primary p-6 text-white">
                  <p className="font-medium">{section.text}</p>
                  <Link href={section.href} className="mt-3 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-primary">
                    {section.label} <ArrowRight className="h-4 w-4" />
                  </Link>
                </div>
              );
              return null;
            })}
          </div>
        )}

        {/* Segunda imagen: insight block */}
        {body.length > 0 && <InsightBlock category={post.category} />}

        {/* Segunda mitad del body */}
        {secondHalf.length > 0 && (
          <div className="space-y-6">
            {secondHalf.map((section, i) => {
              if (section.type === "p") return (
                <p key={i} className="text-base leading-8 text-muted-foreground">{section.text}</p>
              );
              if (section.type === "h2") return (
                <h2 key={i} className="pt-4 text-2xl font-semibold text-navy">{section.text}</h2>
              );
              if (section.type === "ul") return (
                <ul key={i} className="list-disc space-y-2 pl-6 text-muted-foreground">
                  {section.items.map((item, j) => <li key={j}>{item}</li>)}
                </ul>
              );
              if (section.type === "cta") return (
                <div key={i} className="rounded-2xl bg-primary p-6 text-white">
                  <p className="font-medium">{section.text}</p>
                  <Link href={section.href} className="mt-3 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-primary">
                    {section.label} <ArrowRight className="h-4 w-4" />
                  </Link>
                </div>
              );
              return null;
            })}
          </div>
        )}

        {body.length === 0 && (
          <p className="text-base leading-8 text-muted-foreground">Contenido en preparacion. Vuelve pronto.</p>
        )}

        {/* Footer del articulo */}
        <div className="mt-16 flex flex-col gap-4 border-t border-border pt-8 sm:flex-row sm:items-center sm:justify-between">
          <Link href="/blog" className="inline-flex items-center gap-2 text-sm font-medium text-muted-foreground hover:text-primary">
            <ArrowLeft className="h-4 w-4" /> Ver todos los articulos
          </Link>
          <Link href="/demo" className="inline-flex h-10 items-center justify-center rounded-xl bg-primary px-5 text-sm font-semibold text-white transition-transform hover:scale-105">
            Solicitar demo
          </Link>
        </div>
      </article>
    </MarketingLayout>
  );
}
