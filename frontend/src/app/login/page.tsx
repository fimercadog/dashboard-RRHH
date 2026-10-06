import { AuthSplitLayout } from "@/components/auth/auth-split-layout";
import { LoginForm } from "./login-form";
import { DEMO_USERS } from "@/lib/demo-users";

export const dynamic = "force-static";

// Este dominio es un showcase: los atajos de usuarios demo se muestran por
// defecto para que cualquiera entre y pruebe roles. Para un despliegue con
// datos reales de cliente: NEXT_PUBLIC_DEMO_MODE=false y rotar las cuentas.
const demoMode = process.env.NEXT_PUBLIC_DEMO_MODE !== "false";

// Mapa slug → email para el shortcut ?demo=superadmin en la URL.
// Slug = prefijo del email (superadmin, admin, rrhh, supervisor, empleado).
const demoEmails = Object.fromEntries(
  DEMO_USERS.map((u) => [u.email.split("@")[0], u.email])
);

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
