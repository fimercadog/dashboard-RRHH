import Link from "next/link";
import { Logo } from "@/components/brand/logo";
import { ThemeProvider } from "@/components/theme-provider";
import { trustItems } from "@/components/marketing/marketing-data";

export function AuthSplitLayout({ children }: { children: React.ReactNode }) {
  return (
    <ThemeProvider>
      <main className="grid min-h-screen lg:grid-cols-2">
        <section className="relative hidden overflow-hidden bg-[linear-gradient(160deg,#fbe9f0_0%,#f0c9da_100%)] px-12 py-16 lg:flex lg:flex-col lg:justify-center">
          <div className="absolute -left-24 -top-24 h-72 w-72 rounded-full bg-primary/20 blur-3xl" />
          <div className="absolute -bottom-24 -right-16 h-72 w-72 rounded-full bg-primary/10 blur-3xl" />
          <div className="relative max-w-lg">
            <h2 className="text-4xl font-semibold leading-tight tracking-tight text-navy">Gestiona tu equipo desde un solo lugar</h2>
            <p className="mt-4 text-lg leading-8 text-muted-foreground">
              Empleados, asistencia, documentos, vacaciones, permisos y reclutamiento en una sola plataforma.
            </p>
            <div className="mt-10 space-y-4">
              {trustItems.map((item) => {
                const Icon = item.icon;
                return (
                  <div key={item.label} className="flex items-center gap-3 rounded-2xl bg-white/70 p-4 text-sm font-medium text-navy shadow-sm">
                    <Icon className="h-5 w-5 text-primary" /> {item.label}
                  </div>
                );
              })}
            </div>
          </div>
        </section>
        <section className="flex items-center justify-center bg-background px-4 py-12 sm:px-6">
          <div className="w-full max-w-md">
            <Link href="/" className="mb-8 flex justify-center">
              <Logo size="xl" />
            </Link>
            {children}
          </div>
        </section>
      </main>
    </ThemeProvider>
  );
}
