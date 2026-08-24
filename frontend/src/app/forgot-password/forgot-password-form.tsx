"use client";

import { useState } from "react";
import Link from "next/link";
import { AlertCircle, CheckCircle2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { api } from "@/lib/api";

export function ForgotPasswordForm() {
  const [email, setEmail] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);
    setError("");
    setSuccess("");

    try {
      await api.post("/auth/forgot-password", { email });
      setSuccess("Si el correo existe, te enviamos un enlace para restablecer la contraseña.");
    } catch {
      setError("No se pudo enviar el enlace. Revisa el correo e intenta de nuevo.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <form className="mt-6 space-y-4" onSubmit={submit}>
      <Input placeholder="Email" type="email" required value={email} onChange={(event) => setEmail(event.target.value)} />
      {error ? (
        <div className="flex items-center gap-2 rounded-xl bg-destructive/10 px-3 py-2 text-sm text-destructive">
          <AlertCircle className="h-4 w-4" /> {error}
        </div>
      ) : null}
      {success ? (
        <div className="flex items-center gap-2 rounded-xl bg-success/10 px-3 py-2 text-sm text-success">
          <CheckCircle2 className="h-4 w-4" /> {success}
        </div>
      ) : null}
      <Button className="w-full" disabled={loading}>
        {loading ? "Enviando..." : "Enviar enlace"}
      </Button>
      <Link href="/login" className="block text-center text-xs font-medium text-primary hover:underline">
        Volver a iniciar sesion
      </Link>
    </form>
  );
}
