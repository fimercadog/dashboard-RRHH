import type { AuthUser } from "@/lib/auth";

export type DemoLoginResult = { token: string; user: AuthUser };

// En static export no hay servidor Next.js; la contraseña vive en NEXT_PUBLIC_DEMO_PASSWORD.
// ponytail: demo password expuesto al bundle — aceptable para app demo, no usar en producción real con datos sensibles.
export async function demoLogin(email: string): Promise<DemoLoginResult> {
  const password = process.env.NEXT_PUBLIC_DEMO_PASSWORD;
  if (!password) throw new Error("DEMO_PASSWORD no configurado en el servidor");

  const rawBase = process.env.NEXT_PUBLIC_API_URL ?? "/api";
  const apiBase =
    rawBase.startsWith("http") || rawBase.startsWith("/")
      ? rawBase
      : `https://${rawBase}`;

  const res = await fetch(`${apiBase}/auth/login`, {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify({ email, password }),
  });

  if (!res.ok) throw new Error("Credenciales de agente inválidas");
  return res.json() as Promise<DemoLoginResult>;
}
