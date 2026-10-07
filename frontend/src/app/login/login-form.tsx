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
import { DEMO_USERS } from "@/lib/demo-users";
import { demoLogin } from "@/app/actions/demo-login";

// 4 agentes de prueba (Supervisor excluido del selector demo)
const DEMO_AGENTS = DEMO_USERS.filter((u) =>
  ["Super Admin", "Administrador de empresa", "Recursos Humanos", "Empleado"].includes(u.role),
);

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
  // password solo existe en modo real — en demo nunca toca el cliente
  const [password, setPassword] = useState("");
  const [selectedDemo, setSelectedDemo] = useState<number | null>(() => {
    if (!demoMode || !initialEmail) return null;
    const idx = DEMO_AGENTS.findIndex((u) => u.email === initialEmail);
    return idx !== -1 ? idx : null;
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const autoLoginStarted = useRef(false);

  const login = useCallback(
    async (loginEmail = email) => {
      setLoading(true);
      setError("");
      setSuccess("");
      try {
        let result: LoginResponse;
        if (demoMode) {
          // Server Action: la contraseña nunca llega al bundle del cliente
          result = await demoLogin(loginEmail);
        } else {
          const response = await api.post<LoginResponse>("/auth/login", {
            email: loginEmail,
            password,
          });
          result = response.data;
        }
        storeAuthSession(result.user, result.token);
        setSuccess(`Sesion iniciada como ${result.user.roles.join(", ")}`);
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
    [email, password, router, demoMode],
  );

  useEffect(() => {
    if (!autoLogin || autoLoginStarted.current) return;
    autoLoginStarted.current = true;
    void login(initialEmail);
  }, [autoLogin, initialEmail, login]);

  function selectDemoUser(index: number) {
    setSelectedDemo(index);
    setEmail(DEMO_AGENTS[index].email);
    setError("");
  }

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    await login();
  }

  return (
    <form className="mt-6 space-y-4" onSubmit={submit}>
      {demoMode ? (
        <>
          <p className="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
            🧪 Usuarios de prueba
          </p>
          <div className="space-y-2">
            {DEMO_AGENTS.map((user, index) => (
              <button
                key={user.email}
                type="button"
                onClick={() => selectDemoUser(index)}
                className={`w-full rounded-xl border px-4 py-3 text-left transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary ${
                  selectedDemo === index
                    ? "border-primary bg-primary/10 ring-1 ring-primary"
                    : "border-border bg-card hover:border-primary/40 hover:bg-accent"
                }`}
              >
                <p className={`text-sm font-semibold ${selectedDemo === index ? "text-primary" : "text-foreground"}`}>
                  {user.name}
                </p>
                <p className="mt-0.5 text-xs text-muted-foreground">
                  {user.role} · {user.email}
                </p>
              </button>
            ))}
          </div>
        </>
      ) : (
        <>
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
            onChange={(event) => setPassword(event.target.value)}
          />
          <div className="text-right">
            <Link href="/forgot-password" className="text-xs font-medium text-primary hover:underline">
              ¿Olvidaste tu contraseña?
            </Link>
          </div>
        </>
      )}
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
      <Button className="w-full" disabled={loading || (demoMode && selectedDemo === null)}>
        {loading ? "Entrando..." : "Entrar al panel"}
      </Button>
    </form>
  );
}
