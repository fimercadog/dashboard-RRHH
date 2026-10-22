import Link from "next/link";
import { MarketingLayout } from "@/components/marketing/marketing-layout";

export default function NotFound() {
  return (
    <MarketingLayout>
      <main className="flex min-h-[60vh] flex-col items-center justify-center px-4 py-20 text-center sm:px-6 lg:px-8">
        <p className="text-8xl font-black text-primary sm:text-9xl">404</p>
        <h1 className="mt-6 text-3xl font-semibold text-navy sm:text-4xl">Pagina no encontrada</h1>
        <p className="mt-4 max-w-md text-base leading-7 text-muted-foreground">
          La pagina que buscas no existe o fue movida. Puedes volver al inicio o explorar el blog.
        </p>
        <div className="mt-8 flex flex-col gap-3 sm:flex-row">
          <Link href="/" className="inline-flex h-11 items-center justify-center rounded-xl bg-primary px-6 text-sm font-semibold text-white transition-transform hover:scale-105">
            Volver al inicio
          </Link>
          <Link href="/blog" className="inline-flex h-11 items-center justify-center rounded-xl border border-border px-6 text-sm font-semibold text-navy transition-colors hover:bg-muted">
            Ver recursos
          </Link>
        </div>
      </main>
    </MarketingLayout>
  );
}
