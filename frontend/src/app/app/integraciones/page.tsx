"use client";

import { useEffect, useState } from "react";
import { Bot, CheckCircle, MessageCircle, XCircle } from "lucide-react";
import { api } from "@/lib/api";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

interface IntegrationStatus {
  whatsapp: {
    is_connected: boolean;
    is_active: boolean;
  };
  ai: {
    is_connected: boolean;
    is_active: boolean;
    provider: string | null;
  };
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

      {loading && (
        <p className="text-sm text-muted-foreground">Cargando...</p>
      )}

      {error && (
        <p className="text-sm text-destructive">{error}</p>
      )}

      {status && (
        <div className="grid gap-4 sm:grid-cols-2">
          {/* WhatsApp card */}
          <Card>
            <CardHeader className="flex flex-row items-center gap-3 pb-2">
              <MessageCircle className="h-5 w-5 text-green-600" />
              <CardTitle className="text-base">WhatsApp Business</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <ConnectionBadge connected={status.whatsapp.is_connected} />
              {!status.whatsapp.is_connected && (
                <ContactNote />
              )}
            </CardContent>
          </Card>

          {/* AI card */}
          <Card>
            <CardHeader className="flex flex-row items-center gap-3 pb-2">
              <Bot className="h-5 w-5 text-blue-600" />
              <CardTitle className="text-base">Proveedor de IA</CardTitle>
              {status.ai.provider && (
                <CardDescription className="ml-auto text-xs font-medium">
                  {PROVIDER_LABELS[status.ai.provider] ?? status.ai.provider}
                </CardDescription>
              )}
            </CardHeader>
            <CardContent className="space-y-3">
              <ConnectionBadge connected={status.ai.is_connected} />
              {!status.ai.is_connected && (
                <ContactNote />
              )}
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
}

function ConnectionBadge({ connected }: { connected: boolean }) {
  return connected ? (
    <Badge variant="default" className="gap-1 bg-green-600 text-white hover:bg-green-700">
      <CheckCircle className="h-3 w-3" />
      Conectado
    </Badge>
  ) : (
    <Badge variant="secondary" className="gap-1">
      <XCircle className="h-3 w-3" />
      No configurado
    </Badge>
  );
}

function ContactNote() {
  return (
    <p className="text-xs text-muted-foreground">
      No configurado. Contacta al equipo FidelOS para activar esta integración.
    </p>
  );
}
