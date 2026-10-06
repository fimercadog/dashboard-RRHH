"use client";

import { useRef, useState } from "react";
import Link from "next/link";
import { AlertCircle, ArrowLeft, CheckCircle2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { api } from "@/lib/api";

type FormData = {
  name: string;
  company_name: string;
  email: string;
  phone: string;
  employee_count: string;
  priority_module: string;
  message: string;
};

const EMPLOYEE_RANGES = ["1–10", "11–50", "51–200", "201–500", "500+"];
const MODULES = [
  "Recursos Humanos",
  "CRM",
  "Inventario",
  "Compras",
  "Ventas",
  "Finanzas",
  "Contabilidad",
  "Otro",
];

const STEPS = [
  { title: "Cuéntanos sobre ti", subtitle: "¿Cómo te llamamos y dónde trabajas?" },
  { title: "¿Cómo contactarte?", subtitle: "Para que nuestro equipo se ponga en contacto contigo." },
  { title: "Tu empresa", subtitle: "¿Cuántas personas gestionas actualmente?" },
  { title: "¿Qué necesitas?", subtitle: "Cuéntanos qué proceso quieres mejorar." },
  { title: "Revisa y envía", subtitle: "Confirma tu información antes de enviar." },
];

function PillSelector({
  options,
  value,
  onChange,
}: {
  options: string[];
  value: string;
  onChange: (v: string) => void;
}) {
  return (
    <div className="flex flex-wrap gap-2">
      {options.map((opt) => (
        <button
          key={opt}
          type="button"
          onClick={() => onChange(value === opt ? "" : opt)}
          className={`rounded-full border px-4 py-2 text-sm font-medium transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary ${
            value === opt
              ? "border-primary bg-primary/10 text-primary"
              : "border-border bg-card text-foreground hover:border-primary/40 hover:bg-accent"
          }`}
        >
          {opt}
        </button>
      ))}
    </div>
  );
}

function SummaryRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex gap-3 rounded-xl bg-muted px-4 py-3">
      <span className="min-w-[5rem] shrink-0 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
        {label}
      </span>
      <span className="text-sm text-foreground">{value}</span>
    </div>
  );
}

export function ContactWizard() {
  const [step, setStep] = useState(0);
  const [data, setData] = useState<FormData>({
    name: "",
    company_name: "",
    email: "",
    phone: "",
    employee_count: "",
    priority_module: "",
    message: "",
  });
  const [consent, setConsent] = useState(false);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [done, setDone] = useState(false);
  const cardRef = useRef<HTMLFormElement>(null);

  function update(field: keyof FormData, value: string) {
    setData((d) => ({ ...d, [field]: value }));
    setError("");
  }

  function validate(): string {
    if (step === 0 && !data.name.trim()) return "El nombre es obligatorio.";
    if (step === 1) {
      if (!data.email.trim()) return "El email es obligatorio.";
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email))
        return "Ingresa un email valido.";
    }
    if (step === STEPS.length - 1 && !consent)
      return "Debes autorizar el tratamiento de datos para continuar.";
    return "";
  }

  function scrollTop() {
    cardRef.current?.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  function next(e: React.FormEvent) {
    e.preventDefault();
    const err = validate();
    if (err) { setError(err); return; }
    setError("");
    setStep((s) => s + 1);
    scrollTop();
  }

  function back() {
    setError("");
    setStep((s) => s - 1);
    scrollTop();
  }

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    const err = validate();
    if (err) { setError(err); return; }
    setLoading(true);
    setError("");
    try {
      await api.post("/leads", {
        name: data.name.trim(),
        company_name: data.company_name.trim() || null,
        email: data.email.trim(),
        phone: data.phone.trim() || null,
        employee_count: data.employee_count || null,
        priority_module: data.priority_module || null,
        message: data.message.trim() || null,
        source: "contact",
        consent: true,
      });
      setDone(true);
    } catch (err: unknown) {
      const status = (err as { response?: { status?: number } }).response?.status;
      setError(
        status === 429
          ? "Recibimos varios envios seguidos. Espera un momento e intenta de nuevo."
          : status === 422
            ? "Revisa los campos: el nombre y un correo valido son obligatorios."
            : "No se pudo enviar. Intenta de nuevo o escribenos directamente.",
      );
    } finally {
      setLoading(false);
    }
  }

  if (done) {
    return (
      <div className="rounded-4xl border border-border bg-white p-8 text-center shadow-(--marketing-shadow)">
        <CheckCircle2 className="mx-auto mb-4 h-12 w-12 text-primary" />
        <h3 className="text-xl font-semibold text-foreground">¡Gracias!</h3>
        <p className="mt-2 text-sm text-muted-foreground">
          Hemos recibido tu solicitud. Nuestro equipo se pondra en contacto
          contigo pronto.
        </p>
      </div>
    );
  }

  const isLastStep = step === STEPS.length - 1;

  return (
    <form
      ref={cardRef}
      onSubmit={isLastStep ? submit : next}
      className="rounded-4xl border border-border bg-white p-6 shadow-(--marketing-shadow) sm:p-8"
    >
      {/* Progress bar */}
      <div className="mb-6 flex items-center gap-1.5">
        {STEPS.map((_, i) => (
          <div
            key={i}
            className={`h-1.5 rounded-full transition-all duration-300 ${
              i === step
                ? "w-8 bg-primary"
                : i < step
                  ? "w-4 bg-primary/50"
                  : "w-4 bg-border"
            }`}
          />
        ))}
        <span className="ml-auto shrink-0 text-xs text-muted-foreground">
          {step + 1} / {STEPS.length}
        </span>
      </div>

      {/* Step header */}
      <div className="mb-6">
        <h2 className="text-xl font-semibold text-foreground">
          {STEPS[step].title}
        </h2>
        <p className="mt-1 text-sm text-muted-foreground">
          {STEPS[step].subtitle}
        </p>
      </div>

      {/* Step 0 — Nombre + Empresa */}
      {step === 0 && (
        <div className="space-y-4">
          <div>
            <label className="mb-1 block text-sm font-medium text-foreground">
              Nombre <span className="text-destructive">*</span>
            </label>
            <Input
              value={data.name}
              onChange={(e) => update("name", e.target.value)}
              placeholder="Tu nombre"
              autoComplete="name"
            />
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-foreground">
              Empresa
            </label>
            <Input
              value={data.company_name}
              onChange={(e) => update("company_name", e.target.value)}
              placeholder="Nombre de tu empresa"
              autoComplete="organization"
            />
          </div>
        </div>
      )}

      {/* Step 1 — Email + WhatsApp */}
      {step === 1 && (
        <div className="space-y-4">
          <div>
            <label className="mb-1 block text-sm font-medium text-foreground">
              Email <span className="text-destructive">*</span>
            </label>
            <Input
              type="email"
              value={data.email}
              onChange={(e) => update("email", e.target.value)}
              placeholder="tu@empresa.com"
              autoComplete="email"
            />
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-foreground">
              WhatsApp / Teléfono
            </label>
            <Input
              type="tel"
              value={data.phone}
              onChange={(e) => update("phone", e.target.value)}
              placeholder="+57 300 000 0000"
              autoComplete="tel"
            />
          </div>
        </div>
      )}

      {/* Step 2 — Número de empleados */}
      {step === 2 && (
        <div>
          <p className="mb-3 text-sm font-medium text-foreground">
            Selecciona el rango que mejor describe tu equipo
          </p>
          <PillSelector
            options={EMPLOYEE_RANGES}
            value={data.employee_count}
            onChange={(v) => update("employee_count", v)}
          />
        </div>
      )}

      {/* Step 3 — Módulo prioritario + Mensaje */}
      {step === 3 && (
        <div className="space-y-5">
          <div>
            <p className="mb-3 text-sm font-medium text-foreground">
              ¿Qué módulo quieres priorizar?
            </p>
            <PillSelector
              options={MODULES}
              value={data.priority_module}
              onChange={(v) => update("priority_module", v)}
            />
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-foreground">
              Cuéntanos tu necesidad
            </label>
            <textarea
              value={data.message}
              onChange={(e) => update("message", e.target.value)}
              className="min-h-28 w-full rounded-md border border-border bg-card px-3 py-3 text-sm outline-none placeholder:text-muted-foreground focus:ring-2 focus:ring-primary/30"
              placeholder="Describe el proceso que quieres mejorar..."
            />
          </div>
        </div>
      )}

      {/* Step 4 — Resumen */}
      {step === 4 && (
        <div className="space-y-2">
          {data.name && <SummaryRow label="Nombre" value={data.name} />}
          {data.company_name && (
            <SummaryRow label="Empresa" value={data.company_name} />
          )}
          {data.email && <SummaryRow label="Email" value={data.email} />}
          {data.phone && <SummaryRow label="WhatsApp" value={data.phone} />}
          {data.employee_count && (
            <SummaryRow label="Empleados" value={data.employee_count} />
          )}
          {data.priority_module && (
            <SummaryRow label="Módulo" value={data.priority_module} />
          )}
          {data.message && (
            <SummaryRow label="Mensaje" value={data.message} />
          )}
          <label className="mt-3 flex items-start gap-2 pt-1 text-xs leading-5 text-muted-foreground">
            <input
              type="checkbox"
              checked={consent}
              onChange={(e) => {
                setConsent(e.target.checked);
                setError("");
              }}
              className="mt-0.5 h-4 w-4 shrink-0 accent-primary"
            />
            <span>
              Autorizo el tratamiento de mis datos personales para ser
              contactado con fines comerciales, conforme a la Ley 1581 de 2012
              y la{" "}
              <Link
                href="/privacidad"
                className="font-medium text-primary underline"
              >
                Politica de Tratamiento de Datos
              </Link>
              .
            </span>
          </label>
        </div>
      )}

      {/* Error */}
      {error ? (
        <div className="mt-4 flex items-center gap-2 rounded-xl bg-destructive/10 px-3 py-2 text-sm text-destructive">
          <AlertCircle className="h-4 w-4 shrink-0" /> {error}
        </div>
      ) : null}

      {/* Navigation */}
      <div className="mt-6 flex items-center justify-between gap-4">
        {step > 0 ? (
          <button
            type="button"
            onClick={back}
            className="flex items-center gap-1 text-sm font-medium text-muted-foreground hover:text-foreground"
          >
            <ArrowLeft className="h-4 w-4" /> Volver
          </button>
        ) : (
          <div />
        )}
        <Button type="submit" disabled={loading}>
          {isLastStep
            ? loading
              ? "Enviando..."
              : "Enviar solicitud"
            : "Siguiente"}
        </Button>
      </div>
    </form>
  );
}
