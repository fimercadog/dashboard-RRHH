import { AuthSplitLayout } from "@/components/auth/auth-split-layout";
import { LoginForm } from "./login-form";

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
  const initialEmail = params.demo ? demoEmails[params.demo] : undefined;

  return (
    <AuthSplitLayout>
      <h1 className="text-2xl font-semibold text-foreground">Iniciar sesion</h1>
      <p className="mt-2 text-sm text-muted-foreground">Usa un usuario demo para entrar al panel y probar roles.</p>
      <LoginForm initialEmail={initialEmail} autoLogin={Boolean(initialEmail)} />
    </AuthSplitLayout>
  );
}
