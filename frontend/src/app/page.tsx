import Link from "next/link";

export default function Home() {
  return (
    <main className="min-h-screen bg-background px-6 py-10 text-foreground">
      <section className="mx-auto flex min-h-[80vh] max-w-4xl flex-col justify-center gap-6">
        <p className="text-sm font-medium text-primary">FidelOS HRMS</p>
        <h1 className="max-w-2xl text-4xl font-semibold leading-tight">
          Plataforma privada de Recursos Humanos para operar talento, asistencia y solicitudes.
        </h1>
        <p className="max-w-2xl text-muted-foreground">
          La web publica se mantiene separada de la experiencia administrativa. El sistema privado vive en /app.
        </p>
        <Link className="inline-flex h-11 w-fit items-center rounded-md bg-primary px-5 text-sm font-medium text-primary-foreground" href="/app/dashboard">
          Entrar al panel
        </Link>
      </section>
    </main>
  );
}
