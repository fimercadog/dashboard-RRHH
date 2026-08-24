import { AuthSplitLayout } from "@/components/auth/auth-split-layout";
import { ResetPasswordForm } from "./reset-password-form";

export default async function ResetPasswordPage({
  searchParams,
}: {
  searchParams: Promise<{ token?: string; email?: string }>;
}) {
  const params = await searchParams;

  return (
    <AuthSplitLayout>
      <h1 className="text-2xl font-semibold text-foreground">Elegir nueva contraseña</h1>
      <p className="mt-2 text-sm text-muted-foreground">Escribe una contraseña nueva para tu cuenta.</p>
      <ResetPasswordForm token={params.token ?? ""} email={params.email ?? ""} />
    </AuthSplitLayout>
  );
}
