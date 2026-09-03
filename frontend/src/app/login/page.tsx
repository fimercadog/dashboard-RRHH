import { AuthSplitLayout } from "@/components/auth/auth-split-layout";
import { LoginForm } from "./login-form";

// Atajos de usuarios demo en el login. Seguro por defecto: ocultos en produccion.
// La variable, si esta definida, manda en cualquier direccion; sin ella se
// muestran solo en desarrollo. Para el dominio showcase: NEXT_PUBLIC_DEMO_MODE=true.
const demoMode =
  process.env.NEXT_PUBLIC_DEMO_MODE !== undefined && process.env.NEXT_PUBLIC_DEMO_MODE !== ""
    ? process.env.NEXT_PUBLIC_DEMO_MODE !== "false"
    : process.env.NODE_ENV !== "production";

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
      <p className="mt-2 text-sm text-muted-foreground">
        {demoMode
          ? "Usa un usuario demo para entrar al panel y probar roles."
          : "Ingresa con las credenciales de tu cuenta."}
      </p>
      <LoginForm initialEmail={initialEmail} autoLogin={Boolean(initialEmail)} demoMode={demoMode} />
    </AuthSplitLayout>
  );
}
