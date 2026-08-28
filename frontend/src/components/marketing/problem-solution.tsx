import { CheckCircle2, XCircle } from "lucide-react";
import { Reveal } from "./reveal";

const before = ["Excel", "documentos dispersos", "solicitudes por WhatsApp", "correos", "procesos manuales", "informacion dificil de encontrar"];
const after = ["informacion centralizada", "expediente por empleado", "flujos de aprobacion", "reportes", "trazabilidad", "automatizacion"];

export function ProblemSolution() {
  return (
    <section className="bg-muted px-4 py-24 sm:px-6 lg:px-8">
      <div className="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:items-center">
        <Reveal>
          <div>
            <p className="mb-3 text-sm font-semibold uppercase tracking-[0.18em] text-primary">Antes vs ahora</p>
            <h2 className="text-4xl font-semibold tracking-tight text-navy">Menos Excel. Más control.</h2>
            <p className="mt-4 text-base leading-7 text-muted-foreground">La plataforma convierte conversaciones sueltas, archivos y aprobaciones invisibles en procesos claros que RRHH puede medir y mejorar.</p>
          </div>
        </Reveal>
        <div className="grid gap-5 md:grid-cols-2">
          <Reveal delay={0.1}>
            <div className="rounded-3xl border border-border bg-white p-6">
              <p className="mb-5 text-lg font-semibold text-navy">Antes</p>
              <div className="space-y-3">
                {before.map((item) => (
                  <div key={item} className="flex items-center gap-3 text-sm text-muted-foreground"><XCircle className="h-5 w-5 text-destructive" /> {item}</div>
                ))}
              </div>
            </div>
          </Reveal>
          <Reveal delay={0.2}>
            <div className="rounded-3xl bg-navy p-6 text-white">
              <p className="mb-5 text-lg font-semibold">Con DFC Talento Humano</p>
              <div className="space-y-3">
                {after.map((item) => (
                  <div key={item} className="flex items-center gap-3 text-sm text-white/80"><CheckCircle2 className="h-5 w-5 text-success" /> {item}</div>
                ))}
              </div>
            </div>
          </Reveal>
        </div>
      </div>
    </section>
  );
}
