"use client";

import { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { AlertCircle, CheckCircle2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { api } from "@/lib/api";

export function ResetPasswordForm({ token, email }: { token: string; email: string }) {
  const router = useRouter();
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState(false);

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (!token || !email) {
      setError("El enlace no es valido. Solicita uno nuevo.");
      return;
    }

    setLoading(true);
    setError("");

    try {
      await api.post("/auth/reset-password", {
        token,
        email,
        password,
        password_confirmation: passwordConfirmation,
      });
      setSuccess(true);
      setTimeout(() => router.push("/login"), 2000);
    } catch {
      setError("No se pudo restablecer la contraseña. El enlace pudo haber expirado.");
    } finally {
      setLoading(false);
    }
  }

  if (success) {
    return (
      <div className="mt-6 flex items-center gap-2 rounded-xl bg-success/10 px-3 py-2 text-sm text-success">
        <CheckCircle2 className="h-4 w-4" /> Contraseña actualizada. Redirigiendo al inicio de sesion...
      </div>
    );
  }

  return (
    <form className="mt-6 space-y-4" onSubmit={submit}>
      <Input placeholder="Nueva contraseña" type="password" required minLength={8} value={password} onChange={(event) => setPassword(event.target.value)} />
      <Input
        placeholder="Confirmar contraseña"
        type="password"
        required
        minLength={8}
        value={passwordConfirmation}
        onChange={(event) => setPasswordConfirmation(event.target.value)}
      />
      {error ? (
        <div className="flex items-center gap-2 rounded-xl bg-destructive/10 px-3 py-2 text-sm text-destructive">
          <AlertCircle className="h-4 w-4" /> {error}
        </div>
      ) : null}
      <Button className="w-full" disabled={loading}>
        {loading ? "Guardando..." : "Restablecer contraseña"}
      </Button>
      <Link href="/login" className="block text-center text-xs font-medium text-primary hover:underline">
        Volver a iniciar sesion
      </Link>
    </form>
  );
}
