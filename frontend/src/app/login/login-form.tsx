"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { AlertCircle, CheckCircle2 } from "lucide-react";
import { isAxiosError } from "axios";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { api } from "@/lib/api";
import { AuthUser, storeAuthSession } from "@/lib/auth";

const demoUsers: [string, string][] = [
  ["Super Admin", "superadmin@andespeople.co"],
  ["Admin empresa", "admin@andespeople.co"],
  ["RRHH", "rrhh@andespeople.co"],
  ["Supervisor", "supervisor@andespeople.co"],
  ["Empleado", "empleado@andespeople.co"],
];

const DEMO_PASSWORD = "password";

type LoginResponse = {
  token: string;
  user: AuthUser;
};

export function LoginForm({
  initialEmail = "",
  autoLogin = false,
  demoMode = false,
}: {
  initialEmail?: string;
  autoLogin?: boolean;
  demoMode?: boolean;
}) {
  const router = useRouter();
  const [email, setEmail] = useState(initialEmail);
  const [password, setPassword] = useState(demoMode ? DEMO_PASSWORD : "");
  const [selectedDemo, setSelectedDemo] = useState<number | null>(() => {
    if (!demoMode || !initialEmail) return null;
    const idx = demoUsers.findIndex(([, e]) => e === initialEmail);
    return idx !== -1 ? idx : null;
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const autoLoginStarted = useRef(false);

  const login = useCallback(
    async (loginEmail = email, loginPassword = password) => {
      setLoading(true);
      setError("");
      setSuccess("");
      try {
        const response = await api.post<LoginResponse>("/auth/login", {
          email: loginEmail,
          password: loginPassword,
        });
        storeAuthSession(response.data.user, response.data.token);
        setSuccess(`Sesion iniciada como ${response.data.user.roles.join(", ")}`);
        router.push("/app/dashboard");
      } catch (err) {
        if (isAxiosError(err) && err.response?.status === 429) {
          const retryAfter = Number(err.response.headers["retry-after"]) || 60;
          setError(`Demasiados intentos fallidos. Espera ${retryAfter} segundos e intenta de nuevo.`);
        } else {
          setError("No se pudo iniciar sesion. Revisa el usuario y la contraseña.");
        }
      } finally {
        setLoading(false);
      }
    },
    [email, password, router],
  );

  useEffect(() => {
    if (!autoLogin || autoLoginStarted.current) return;
    autoLoginStarted.current = true;
    void login(initialEmail, DEMO_PASSWORD);
  }, [autoLogin, initialEmail, login]);

  function selectDemoUser(index: number) {
    const [, userEmail] = demoUsers[index];
    setSelectedDemo(index);
    setEmail(userEmail);
    setPassword(DEMO_PASSWORD);
    setError("");
  }

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    await login();
  }

  return (
    <>
      <form className="mt-6 space-y-4" onSubmit={submit}>
        <Input
          placeholder="Email"
          type="email"
          value={email}
          onChange={(event) => setEmail(event.target.value)}
        />
        <Input
          placeholder="Contraseña"
          type="password"
          value={password}
          readOnly={demoMode}
          onChange={demoMode ? undefined : (event) => setPassword(event.target.value)}
          className={demoMode ? "cursor-not-allowed opacity-60" : ""}
        />
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

      {demoMode ? (
        <div className="mt-6 space-y-2">
          <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            Usuarios demo
          </p>
          {demoUsers.map(([role, userEmail], index) => (
            <button
              key={userEmail}
              type="button"
              onClick={() => selectDemoUser(index)}
              className={`w-full rounded-xl border px-4 py-3 text-left transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary ${
                selectedDemo === index
                  ? "border-primary bg-primary/10 ring-1 ring-primary"
                  : "border-border bg-card hover:border-primary/40 hover:bg-accent"
              }`}
            >
              <p
                className={`text-sm font-semibold ${
                  selectedDemo === index ? "text-primary" : "text-foreground"
                }`}
              >
                {role}
              </p>
              <p className="mt-0.5 text-xs text-muted-foreground">{userEmail}</p>
            </button>
          ))}
        </div>
      ) : null}
    </>
  );
}
