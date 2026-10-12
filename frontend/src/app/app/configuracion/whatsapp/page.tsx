"use client";

import { useState, useEffect } from "react";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { CheckCircle, AlertCircle, Copy, RefreshCw, TestTube } from "lucide-react";

interface WhatsappSettings {
  id: number;
  phone_number_id: string | null;
  waba_id: string | null;
  has_access_token: boolean;
  has_webhook_token: boolean;
  is_active: boolean;
  webhook_verified_at: string | null;
  is_connected: boolean;
}

export default function WhatsappConfigPage() {
  const [settings, setSettings] = useState<WhatsappSettings | null>(null);
  const [form, setForm] = useState({ phone_number_id: "", waba_id: "", access_token: "", webhook_verify_token: "" });
  const [webhookToken, setWebhookToken] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [testing, setTesting] = useState(false);
  const [generatingToken, setGeneratingToken] = useState(false);
  const [testResult, setTestResult] = useState<{ status: string; message?: string } | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadSettings();
  }, []);

  async function loadSettings() {
    setLoading(true);
    try {
      const res = await api.get("/settings/whatsapp");
      const data: WhatsappSettings = res.data;
      setSettings(data);
      setForm({ phone_number_id: data.phone_number_id ?? "", waba_id: data.waba_id ?? "", access_token: "", webhook_verify_token: "" });
    } catch {
      setError("No se pudo cargar la configuración de WhatsApp.");
    } finally {
      setLoading(false);
    }
  }

  async function handleSave() {
    setSaving(true);
    setError(null);
    try {
      const payload: Record<string, string | boolean> = {
        phone_number_id: form.phone_number_id,
        waba_id: form.waba_id,
      };
      if (form.access_token) payload.access_token = form.access_token;
      if (form.webhook_verify_token) payload.webhook_verify_token = form.webhook_verify_token;

      const res = await api.put("/settings/whatsapp", payload);
      setSettings(res.data);
      setForm((f) => ({ ...f, access_token: "", webhook_verify_token: "" }));
    } catch {
      setError("Error al guardar la configuración.");
    } finally {
      setSaving(false);
    }
  }

  async function handleGenerateToken() {
    setGeneratingToken(true);
    try {
      const res = await api.post("/settings/whatsapp/generate-token", {});
      setWebhookToken(res.data.webhook_verify_token);
      await loadSettings();
    } catch {
      setError("Error al generar el token.");
    } finally {
      setGeneratingToken(false);
    }
  }

  async function handleTest() {
    setTesting(true);
    setTestResult(null);
    try {
      const res = await api.post("/settings/whatsapp/test", {});
      setTestResult(res.data);
    } catch {
      setTestResult({ status: "error", message: "Error al conectar con el servidor." });
    } finally {
      setTesting(false);
    }
  }

  function copyToClipboard(text: string) {
    navigator.clipboard.writeText(text).catch(() => undefined);
  }

  if (loading) return <div className="p-8 text-muted-foreground">Cargando configuración…</div>;

  const webhookUrl = typeof window !== "undefined"
    ? `${window.location.origin}/api/webhooks/wa/[TU_VERIFY_TOKEN]`
    : "/api/webhooks/wa/[TU_VERIFY_TOKEN]";

  return (
    <div className="p-6 max-w-2xl space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Configuración de WhatsApp</h1>
          <p className="text-sm text-muted-foreground mt-1">Conecta tu número de WhatsApp Business con FidelOS.</p>
        </div>
        {settings?.is_connected ? (
          <Badge variant="default" className="flex items-center gap-1">
            <CheckCircle className="h-3 w-3" /> Conectado
          </Badge>
        ) : (
          <Badge variant="secondary" className="flex items-center gap-1">
            <AlertCircle className="h-3 w-3" /> Sin conectar
          </Badge>
        )}
      </div>

      {error && (
        <div className="rounded-md border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
          {error}
        </div>
      )}

      {/* Credenciales Meta */}
      <Card>
        <CardHeader>
          <CardTitle>Credenciales Meta</CardTitle>
          <CardDescription>Obtén estos valores desde Meta for Developers → Tu App → WhatsApp → Configuración.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <label className="text-sm font-medium text-foreground" htmlFor="phone_number_id">Phone Number ID</label>
            <Input id="phone_number_id" placeholder="123456789012345" value={form.phone_number_id} onChange={(e) => setForm((f) => ({ ...f, phone_number_id: e.target.value }))} />
          </div>
          <div className="space-y-2">
            <label className="text-sm font-medium text-foreground" htmlFor="waba_id">WhatsApp Business Account ID (WABA)</label>
            <Input id="waba_id" placeholder="987654321098765" value={form.waba_id} onChange={(e) => setForm((f) => ({ ...f, waba_id: e.target.value }))} />
          </div>
          <div className="space-y-2">
            <label className="text-sm font-medium text-foreground" htmlFor="access_token">
              Access Token {settings?.has_access_token && <span className="text-green-600 text-xs">(guardado)</span>}
            </label>
            <Input id="access_token" type="password" placeholder={settings?.has_access_token ? "••••••••• (dejar en blanco para conservar)" : "EAAxxxxx..."} value={form.access_token} onChange={(e) => setForm((f) => ({ ...f, access_token: e.target.value }))} />
          </div>
        </CardContent>
      </Card>

      {/* Webhook */}
      <Card>
        <CardHeader>
          <CardTitle>Webhook</CardTitle>
          <CardDescription>Configura estas URLs en Meta for Developers → Webhooks → Mensajes.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <label className="text-sm font-medium text-foreground">URL del webhook (para Meta)</label>
            <div className="flex gap-2">
              <Input readOnly value={webhookUrl} className="font-mono text-sm" />
              <Button variant="outline" size="icon" onClick={() => copyToClipboard(webhookUrl)}>
                <Copy className="h-4 w-4" />
              </Button>
            </div>
            <p className="text-xs text-muted-foreground">Reemplaza [TU_VERIFY_TOKEN] con el token generado abajo.</p>
          </div>
          <div className="space-y-2">
            <label className="text-sm font-medium text-foreground">Verify Token</label>
            {webhookToken ? (
              <div className="flex gap-2">
                <Input readOnly value={webhookToken} className="font-mono text-sm" />
                <Button variant="outline" size="icon" onClick={() => copyToClipboard(webhookToken)}>
                  <Copy className="h-4 w-4" />
                </Button>
              </div>
            ) : (
              <p className="text-sm text-muted-foreground">{settings?.has_webhook_token ? "Token guardado (genera uno nuevo para reemplazarlo)." : "Genera un token seguro para usarlo en Meta."}</p>
            )}
            <Button variant="outline" size="sm" disabled={generatingToken} onClick={handleGenerateToken}>
              <RefreshCw className={`h-4 w-4 mr-2 ${generatingToken ? "animate-spin" : ""}`} />
              {generatingToken ? "Generando…" : "Generar verify token"}
            </Button>
            {settings?.webhook_verified_at && (
              <p className="text-xs text-green-600 flex items-center gap-1">
                <CheckCircle className="h-3 w-3" />
                Webhook verificado el {new Date(settings.webhook_verified_at).toLocaleString()}
              </p>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Test */}
      {testResult && (
        <div className={`rounded-md border px-4 py-3 text-sm ${testResult.status.includes("ok") ? "border-green-300 bg-green-50 text-green-800" : "border-destructive/30 bg-destructive/10 text-destructive"}`}>
          <strong>{testResult.status}</strong>{testResult.message ? ` — ${testResult.message}` : ""}
        </div>
      )}

      <div className="flex gap-3">
        <Button onClick={handleSave} disabled={saving}>
          {saving ? "Guardando…" : "Guardar configuración"}
        </Button>
        <Button variant="outline" onClick={handleTest} disabled={testing}>
          <TestTube className="h-4 w-4 mr-2" />
          {testing ? "Probando…" : "Probar conexión"}
        </Button>
      </div>
    </div>
  );
}
