import { AuthSplitLayout } from "@/components/auth/auth-split-layout";
import { ForgotPasswordForm } from "./forgot-password-form";

export default function ForgotPasswordPage() {
  return (
    <AuthSplitLayout>
      <h1 className="text-2xl font-semibold text-foreground">Recuperar contraseña</h1>
      <p className="mt-2 text-sm text-muted-foreground">Ingresa tu correo y te enviaremos un enlace para restablecerla.</p>
      <ForgotPasswordForm />
    </AuthSplitLayout>
  );
}
