import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

export function ContactForm({ demo = false }: { demo?: boolean }) {
  return (
    <form className="rounded-4xl border border-border bg-white p-6 shadow-(--marketing-shadow)">
      <div className="grid gap-4 sm:grid-cols-2">
        <Input placeholder="Nombre" aria-label="Nombre" />
        <Input placeholder="Empresa" aria-label="Empresa" />
        <Input placeholder="Email" aria-label="Email" type="email" />
        <Input placeholder="WhatsApp" aria-label="WhatsApp" />
        <Input placeholder="Numero de empleados" aria-label="Numero de empleados" />
        <Input placeholder="Modulo prioritario" aria-label="Modulo prioritario" />
      </div>
      <textarea className="mt-4 min-h-32 w-full rounded-md border border-border bg-card px-3 py-3 text-sm outline-none placeholder:text-muted-foreground" placeholder="Mensaje" aria-label="Mensaje" />
      <label className="mt-4 flex items-start gap-2 text-xs leading-5 text-muted-foreground">
        <input
          type="checkbox"
          name="consent"
          required
          className="mt-0.5 h-4 w-4 shrink-0 accent-primary"
        />
        <span>
          Autorizo el tratamiento de mis datos personales para ser contactado con fines
          comerciales, conforme a la Ley 1581 de 2012 y a la{" "}
          <Link href="/privacidad" className="font-medium text-primary underline">
            Politica de Tratamiento de Datos
          </Link>
          .
        </span>
      </label>
      <Button className="mt-4 w-full">{demo ? "Solicitar demostracion" : "Enviar mensaje"}</Button>
    </form>
  );
}
