import Link from "next/link";
import { ArrowRight } from "lucide-react";
import { Reveal } from "./reveal";

export function CTASection() {
  return (
    <section className="px-4 py-20 sm:px-6 lg:px-8">
      <Reveal className="mx-auto max-w-7xl overflow-hidden rounded-4xl bg-navy px-6 py-14 text-white shadow-(--marketing-shadow) sm:px-10 lg:px-14">
        <div className="grid gap-8 lg:grid-cols-[1.4fr_0.6fr] lg:items-center">
          <div>
            <p className="text-sm font-semibold uppercase tracking-[0.18em] text-primary">Gestion completa de personas</p>
            <h2 className="mt-3 max-w-3xl text-3xl font-semibold tracking-tight sm:text-4xl">Ordena empleados, asistencia, documentos y solicitudes antes de que el crecimiento duplique el trabajo operativo.</h2>
          </div>
          <div className="flex flex-col gap-3 sm:flex-row lg:flex-col">
            <Link className="inline-flex h-12 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-primary px-5 text-sm font-semibold text-white transition-transform duration-200 hover:scale-105 active:scale-95" href="/demo">Solicitar demo <ArrowRight className="h-4 w-4" /></Link>
            <Link className="inline-flex h-12 items-center justify-center whitespace-nowrap rounded-xl border border-white/20 px-5 text-sm font-semibold text-white transition-transform duration-200 hover:scale-105 active:scale-95" href="/producto">Ver producto</Link>
          </div>
        </div>
      </Reveal>
    </section>
  );
}
