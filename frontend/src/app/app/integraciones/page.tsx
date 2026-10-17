"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { ArrowRight, Bot, CheckCircle, MessageCircle, Settings, Sheet, Video, XCircle } from "lucide-react";
import { api } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

interface IntegrationStatus {
  whatsapp: { is_connected: boolean; is_active: boolean };
  ai: { is_connected: boolean; is_active: boolean; provider: string | null };
  zoom: { is_connected: boolean; is_active: boolean };
  google_sheets: { is_connected: boolean; is_active: boolean };
}

const PROVIDER_LABELS: Record<string, string> = {
  openai: "OpenAI",
  anthropic: "Anthropic",
  openai_compat: "OpenAI Compatible",
};

export default function IntegracionesPage() {
  const [status, setStatus] = useState<IntegrationStatus | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api
      .get<IntegrationStatus>("/settings/integration-status")
      .then((res) => setStatus(res.data))
      .catch(() => setError("No se pudo cargar el estado de las integraciones."))
      .finally(() => setLoading(false));
  }, []);

  return (
    <div className="p-6 max-w-2xl mx-auto space-y-6">
      <div>
        <h1 className="text-2xl font-black">Integraciones</h1>
        <p className="text-muted-foreground text-sm mt-1">
          Estado de las integraciones activas para tu empresa.
        </p>
      </div>

      {loading && <p className="text-sm text-muted-foreground">Cargando...</p>}
      {error && <p className="text-sm text-destructive">{error}</p>}

      {status && (
        <div className="grid gap-4 sm:grid-cols-2">
          <IntegrationCard
            icon={<MessageCircle className="h-5 w-5 text-green-600" />}
            title="WhatsApp Business"
            connected={status.whatsapp.is_connected}
            configHref="/app/configuracion/whatsapp"
          />

          <IntegrationCard
            icon={<Bot className="h-5 w-5 text-blue-600" />}
            title="Proveedor de IA"
            connected={status.ai.is_connected}
            subtitle={status.ai.provider ? (PROVIDER_LABELS[status.ai.provider] ?? status.ai.provider) : undefined}
            configHref="/app/configuracion/ia"
          />

          <IntegrationCard
            icon={<Sheet className="h-5 w-5 text-green-600" />}
            title="Google Sheets"
            connected={status.google_sheets.is_connected}
            configHref="/app/configuracion/google-sheets"
          />

          <IntegrationCard
            icon={<Video className="h-5 w-5 text-blue-500" />}
            title="Zoom"
            connected={status.zoom.is_connected}
            configHref="/app/configuracion/zoom"
          />
        </div>
      )}
    </div>
  );
}

function IntegrationCard({
  icon,
  title,
  subtitle,
  connected,
  configHref,
  note,
}: {
  icon: React.ReactNode;
  title: string;
  subtitle?: string;
  connected: boolean;
  configHref?: string;
  note?: string;
}) {
  return (
    <Card>
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            {icon}
            <CardTitle className="text-base">{title}</CardTitle>
          </div>
          {subtitle && (
            <span className="text-xs text-muted-foreground font-medium">{subtitle}</span>
          )}
        </div>
      </CardHeader>
      <CardContent className="space-y-3">
        <div className="flex items-center gap-2">
          {connected ? (
            <CheckCircle className="h-4 w-4 text-green-600" />
          ) : (
            <XCircle className="h-4 w-4 text-muted-foreground" />
          )}
          <span className={`text-sm font-medium ${connected ? "text-green-600" : "text-muted-foreground"}`}>
            {connected ? "Conectado" : "No configurado"}
          </span>
        </div>

        {configHref && (
          <Link
            href={configHref}
            className="flex items-center gap-1 text-xs text-primary hover:underline"
          >
            <Settings className="h-3 w-3" />
            Configurar credenciales
            <ArrowRight className="h-3 w-3" />
          </Link>
        )}

        {note && !connected && (
          <p className="text-xs text-muted-foreground">{note}</p>
        )}
      </CardContent>
    </Card>
  );
}
