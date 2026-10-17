"use client";

import { useState, useEffect } from "react";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { CheckCircle, AlertCircle, TestTube, Video } from "lucide-react";

interface ZoomSettings {
  id: number;
  account_id: string | null;
  client_id: string | null;
  has_client_secret: boolean;
  is_active: boolean;
  is_connected: boolean;
  connected_at: string | null;
}

export default function ZoomConfigPage() {
  const [settings, setSettings] = useState<ZoomSettings | null>(null);
  const [form, setForm] = useState({ account_id: "", client_id: "", client_secret: "" });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [testing, setTesting] = useState(false);
  const [testResult, setTestResult] = useState<{ status: string; message?: string } | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => { loadSettings(); }, []);

  async function loadSettings() {
    setLoading(true);
    try {
      const res = await api.get("/settings/zoom");
      const data: ZoomSettings = res.data;
      setSettings(data);
      setForm({ account_id: data.account_id ?? "", client_id: data.client_id ?? "", client_secret: "" });
    } catch {
      setError("No se pudo cargar la configuración de Zoom.");
    } finally {
      setLoading(false);
    }
  }

  async function handleSave() {
    setSaving(true);
    setError(null);
    try {
      const payload: Record<string, string> = {
        account_id: form.account_id,
        client_id: form.client_id,
      };
      if (form.client_secret) payload.client_secret = form.client_secret;

      const res = await api.put("/settings/zoom", payload);
      setSettings(res.data);
      setForm((f) => ({ ...f, client_secret: "" }));
    } catch {
      setError("Error al guardar la configuración.");
    } finally {
      setSaving(false);
    }
  }

  async function handleTest() {
    setTesting(true);
    setTestResult(null);
    try {
      const res = await api.post("/settings/zoom/test", {});
      setTestResult(res.data);
      await loadSettings();
    } catch {
      setTestResult({ status: "error", message: "Error al conectar con el servidor." });
    } finally {
      setTesting(false);
    }
  }

  if (loading) return <div className="p-8 text-muted-foreground">Cargando configuración…</div>;

  return (
    <div className="p-6 max-w-2xl space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Configuración de Zoom</h1>
          <p className="text-sm text-muted-foreground mt-1">
            Conecta tu cuenta Zoom con FidelOS para videollamadas desde el sistema.
          </p>
        </div>
        {settings?.is_connected ? (
          <Badge variant="default" className="flex items-center gap-1 bg-blue-600 hover:bg-blue-700">
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

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Video className="h-4 w-4" /> Credenciales Zoom
          </CardTitle>
          <CardDescription>
            Obtén estos valores en marketplace.zoom.us → Tu App Server-to-Server OAuth → App Credentials.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <label className="text-sm font-medium" htmlFor="account_id">Account ID</label>
            <Input
              id="account_id"
              placeholder="AbCdEfGhIj1234567890AB"
              value={form.account_id}
              onChange={(e) => setForm((f) => ({ ...f, account_id: e.target.value }))}
            />
          </div>
          <div className="space-y-2">
            <label className="text-sm font-medium" htmlFor="client_id">Client ID</label>
            <Input
              id="client_id"
              placeholder="aBcDeFgHiJ1234567890"
              value={form.client_id}
              onChange={(e) => setForm((f) => ({ ...f, client_id: e.target.value }))}
            />
          </div>
          <div className="space-y-2">
            <label className="text-sm font-medium" htmlFor="client_secret">
              Client Secret{" "}
              {settings?.has_client_secret && (
                <span className="text-green-600 text-xs">(guardado)</span>
              )}
            </label>
            <Input
              id="client_secret"
              type="password"
              placeholder={
                settings?.has_client_secret
                  ? "••••••••• (dejar en blanco para conservar)"
                  : "Tu client secret de Zoom"
              }
              value={form.client_secret}
              onChange={(e) => setForm((f) => ({ ...f, client_secret: e.target.value }))}
            />
          </div>
        </CardContent>
      </Card>

      {testResult && (
        <div
          className={`rounded-md border px-4 py-3 text-sm ${
            testResult.status === "ok"
              ? "border-green-300 bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-300"
              : "border-destructive/30 bg-destructive/10 text-destructive"
          }`}
        >
          <strong>{testResult.status === "ok" ? "✓ Conexión exitosa" : "✗ Error"}</strong>
          {testResult.message ? ` — ${testResult.message}` : ""}
        </div>
      )}

      {settings?.connected_at && (
        <p className="text-xs text-green-600 flex items-center gap-1">
          <CheckCircle className="h-3 w-3" />
          Última conexión verificada: {new Date(settings.connected_at).toLocaleString()}
        </p>
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
