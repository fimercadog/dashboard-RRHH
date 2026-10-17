"use client";

import { useState, useEffect } from "react";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { CheckCircle, AlertCircle, TestTube, Sheet } from "lucide-react";

interface GoogleSheetsSettings {
  id: number;
  has_api_key: boolean;
  is_active: boolean;
  is_connected: boolean;
  connected_at: string | null;
}

export default function GoogleSheetsConfigPage() {
  const [settings, setSettings] = useState<GoogleSheetsSettings | null>(null);
  const [apiKey, setApiKey] = useState("");
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [testing, setTesting] = useState(false);
  const [testResult, setTestResult] = useState<{ status: string; message?: string } | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => { loadSettings(); }, []);

  async function loadSettings() {
    setLoading(true);
    try {
      const res = await api.get("/settings/google-sheets");
      setSettings(res.data);
    } catch {
      setError("No se pudo cargar la configuración de Google Sheets.");
    } finally {
      setLoading(false);
    }
  }

  async function handleSave() {
    setSaving(true);
    setError(null);
    try {
      const payload: Record<string, string> = {};
      if (apiKey) payload.api_key = apiKey;
      const res = await api.put("/settings/google-sheets", payload);
      setSettings(res.data);
      setApiKey("");
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
      const res = await api.post("/settings/google-sheets/test", {});
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
          <h1 className="text-2xl font-bold">Configuración de Google Sheets</h1>
          <p className="text-sm text-muted-foreground mt-1">
            Conecta Google Sheets para importar y exportar datos desde el sistema.
          </p>
        </div>
        {settings?.is_connected ? (
          <Badge variant="default" className="flex items-center gap-1 bg-green-600 hover:bg-green-700">
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
            <Sheet className="h-4 w-4 text-green-600" /> Credenciales Google
          </CardTitle>
          <CardDescription>
            Obtén tu API Key en console.cloud.google.com → APIs y servicios → Credenciales.
            Activa la API de Google Sheets en tu proyecto y asegúrate de que las hojas sean públicas.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <label className="text-sm font-medium" htmlFor="api_key">
              API Key{" "}
              {settings?.has_api_key && (
                <span className="text-green-600 text-xs">(guardada)</span>
              )}
            </label>
            <Input
              id="api_key"
              type="password"
              placeholder={
                settings?.has_api_key
                  ? "••••••••• (dejar en blanco para conservar)"
                  : "AIzaSy..."
              }
              value={apiKey}
              onChange={(e) => setApiKey(e.target.value)}
            />
          </div>

          <div className="rounded-md bg-muted px-4 py-3 text-xs text-muted-foreground space-y-1">
            <p className="font-medium text-foreground">Requisitos de la hoja</p>
            <p>• La hoja debe ser pública (cualquiera con el enlace puede ver).</p>
            <p>• Uso: Archivo → Compartir → Cualquier persona con el enlace → Lector.</p>
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
          Última verificación: {new Date(settings.connected_at).toLocaleString()}
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
