"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { AlertCircle, CheckCircle2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { api } from "@/lib/api";
import { AuthUser, storeAuthSession } from "@/lib/auth";

const demoUsers = [
  ["Super Admin", "superadmin@andespeople.co"],
  ["Admin empresa", "admin@andespeople.co"],
  ["RRHH", "rrhh@andespeople.co"],
  ["Supervisor", "supervisor@andespeople.co"],
  ["Empleado", "empleado@andespeople.co"],
];

type LoginResponse = {
  token: string;
  user: AuthUser;
};

export function LoginForm({
  initialEmail = "admin@andespeople.co",
  autoLogin = false,
}: {
  initialEmail?: string;
  autoLogin?: boolean;
}) {
  const router = useRouter();
  const [email, setEmail] = useState(initialEmail);
  const [password, setPassword] = useState("password");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const autoLoginStarted = useRef(false);

  const login = useCallback(async (selectedEmail = email, selectedPassword = password) => {
    setLoading(true);
    setError("");
    setSuccess("");

    try {
      const response = await api.post<LoginResponse>("/auth/login", { email: selectedEmail, password: selectedPassword });
      storeAuthSession(response.data.token, response.data.user);
      setSuccess(`Sesion iniciada como ${response.data.user.roles.join(", ")}`);
      router.push("/app/dashboard");
    } catch {
      setError("No se pudo iniciar sesion. Revisa el usuario y la contraseña.");
    } finally {
      setLoading(false);
    }
  }, [email, password, router]);

  useEffect(() => {
    if (!autoLogin || autoLoginStarted.current) return;
    autoLoginStarted.current = true;
    void login(initialEmail, "password");
  }, [autoLogin, initialEmail, login]);

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    await login();
  }

  return (
    <>
      <form className="mt-6 space-y-4" onSubmit={submit}>
        <Input placeholder="Email" type="email" value={email} onChange={(event) => setEmail(event.target.value)} />
        <Input placeholder="Password" type="password" value={password} onChange={(event) => setPassword(event.target.value)} />
        <div className="text-right">
          <Link href="/forgot-password" className="text-xs font-medium text-primary hover:underline">
            ¿Olvidaste tu contraseña?
          </Link>
        </div>
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
          {loading ? "Entrando..." : "Entrar al panel"}
        </Button>
      </form>
      <div className="mt-8 rounded-2xl bg-muted p-4">
        <p className="text-sm font-semibold text-foreground">Usuarios demo</p>
        <p className="mt-1 text-xs text-muted-foreground">Password para todos: password</p>
        <div className="mt-4 space-y-2">
          {demoUsers.map(([role, userEmail]) => (
            <button
              key={userEmail}
              type="button"
              className="w-full rounded-xl bg-card px-3 py-2 text-left text-xs transition hover:bg-accent"
              onClick={() => {
                setEmail(userEmail);
                setPassword("password");
              }}
            >
              <p className="font-medium text-foreground">{role}</p>
              <p className="text-muted-foreground">{userEmail}</p>
            </button>
          ))}
        </div>
      </div>
    </>
  );
}
