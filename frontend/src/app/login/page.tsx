import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

export default function LoginPage() {
  return (
    <main className="grid min-h-screen place-items-center bg-muted px-4">
      <section className="w-full max-w-md rounded-[2rem] border border-border bg-white p-8 shadow-[var(--marketing-shadow)]">
        <Link href="/" className="mb-8 block text-center text-xl font-bold text-navy">FidelOS HRMS</Link>
        <h1 className="text-2xl font-semibold text-navy">Iniciar sesion</h1>
        <p className="mt-2 text-sm text-muted-foreground">Pantalla visual preparada para conectar autenticacion real.</p>
        <div className="mt-6 space-y-4">
          <Input placeholder="Email" type="email" />
          <Input placeholder="Password" type="password" />
          <Button className="w-full">Entrar al panel</Button>
        </div>
      </section>
    </main>
  );
}
