import { AuthSplitLayout } from "@/components/auth/auth-split-layout";
import { LoginForm } from "./login-form";

export const dynamic = "force-static";

// Este dominio es un showcase: los atajos de usuarios demo se muestran por
// defecto para que cualquiera entre y pruebe roles. Para un despliegue con
// datos reales de cliente: NEXT_PUBLIC_DEMO_MODE=false y rotar las cuentas.
const demoMode = process.env.NEXT_PUBLIC_DEMO_MODE !== "false";

const demoEmails: Record<string, string> = {
  superadmin: "superadmin@andespeople.co",
  admin: "admin@andespeople.co",
  rrhh: "rrhh@andespeople.co",
  supervisor: "supervisor@andespeople.co",
  empleado: "empleado@andespeople.co",
};

export default async function LoginPage({
  searchParams,
}: {
  searchParams: Promise<{ demo?: string }>;
}) {
  const params = await searchParams;
  const initialEmail = demoMode && params.demo ? demoEmails[params.demo] : undefined;

  return (
    <AuthSplitLayout>
      <h1 className="text-2xl font-semibold text-foreground">Iniciar sesion</h1>
      {demoMode ? (
        <div className="mt-3 rounded-xl border border-primary/20 bg-primary/10 px-4 py-3">
          <p className="text-sm font-semibold text-primary">
            Selecciona un usuario demo — entra al panel y prueba cada rol sin escribir credenciales.
          </p>
        </div>
      ) : (
        <p className="mt-2 text-sm text-muted-foreground">
          Ingresa con las credenciales de tu cuenta.
        </p>
      )}
      <LoginForm initialEmail={initialEmail} autoLogin={Boolean(initialEmail)} demoMode={demoMode} />
    </AuthSplitLayout>
  );
}
