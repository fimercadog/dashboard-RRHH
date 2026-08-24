"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { AlertCircle, CheckCircle2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { api } from "@/lib/api";

const demoUsers = [
  ["Super Admin", "superadmin@andespeople.co"],
  ["Admin empresa", "admin@andespeople.co"],
  ["RRHH", "rrhh@andespeople.co"],
  ["Supervisor", "supervisor@andespeople.co"],
  ["Empleado", "empleado@andespeople.co"],
];

type LoginResponse = {
  token: string;
  user: {
    name: string;
    email: string;
    roles: string[];
    permissions: string[];
  };
};

export function LoginForm() {
  const router = useRouter();
  const [email, setEmail] = useState("admin@andespeople.co");
  const [password, setPassword] = useState("password");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);
    setError("");
    setSuccess("");

    try {
      const response = await api.post<LoginResponse>("/auth/login", { email, password });
      window.localStorage.setItem("hrms_token", response.data.token);
      window.localStorage.setItem("hrms_user", JSON.stringify(response.data.user));
      setSuccess(`Sesion iniciada como ${response.data.user.roles.join(", ")}`);
      router.push("/app/dashboard");
    } catch {
      setError("No se pudo iniciar sesion. Revisa el usuario y la contraseña.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <>
      <form className="mt-6 space-y-4" onSubmit={submit}>
        <Input placeholder="Email" type="email" value={email} onChange={(event) => setEmail(event.target.value)} />
        <Input placeholder="Password" type="password" value={password} onChange={(event) => setPassword(event.target.value)} />
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
        <p className="text-sm font-semibold text-navy">Usuarios demo</p>
        <p className="mt-1 text-xs text-muted-foreground">Password para todos: password</p>
        <div className="mt-4 space-y-2">
          {demoUsers.map(([role, userEmail]) => (
            <button
              key={userEmail}
              type="button"
              className="w-full rounded-xl bg-white px-3 py-2 text-left text-xs transition hover:bg-accent"
              onClick={() => {
                setEmail(userEmail);
                setPassword("password");
              }}
            >
              <p className="font-medium text-navy">{role}</p>
              <p className="text-muted-foreground">{userEmail}</p>
            </button>
          ))}
        </div>
      </div>
    </>
  );
}
