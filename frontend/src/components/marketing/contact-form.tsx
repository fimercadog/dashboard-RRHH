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
      <Button className="mt-4 w-full">{demo ? "Solicitar demostracion" : "Enviar mensaje"}</Button>
    </form>
  );
}
