"use client";

import { useState, useEffect, useCallback } from "react";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { CheckCircle, AlertCircle, TestTube } from "lucide-react";

interface AiSettings {
  id: number;
  provider: string;
  model: string | null;
  has_api_key: boolean;
  base_url: string | null;
  is_active: boolean;
  last_tested_at: string | null;
  is_connected: boolean;
}

export default function AiConfigPage() {
  const [settings, setSettings] = useState<AiSettings | null>(null);
  const [models, setModels] = useState<string[]>([]);
  const [form, setForm] = useState({ provider: "openai", model: "", api_key: "", base_url: "" });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [testing, setTesting] = useState(false);
  const [testResult, setTestResult] = useState<{ status: string; message?: string; latency?: number } | null>(null);
  const [error, setError] = useState<string | null>(null);

  const loadModels = useCallback(async () => {
    try {
      const res = await api.get("/settings/ai/models");
      setModels(res.data ?? []);
    } catch { /* silencioso — lista estática */ }
  }, []);

  const loadSettings = useCallback(async () => {
    setLoading(true);
    try {
      const res = await api.get("/settings/ai");
      const data: AiSettings = res.data;
      setSettings(data);
      setForm({ provider: data.provider, model: data.model ?? "", api_key: "", base_url: data.base_url ?? "" });
      loadModels();
    } catch {
      setError("No se pudo cargar la configuración de IA.");
    } finally {
      setLoading(false);
    }
  }, [loadModels]);

  useEffect(() => { loadSettings(); }, [loadSettings]);

  async function handleSave() {
    setSaving(true);
    setError(null);
    try {
      const payload: Record<string, string | boolean> = {
        provider: form.provider,
        model: form.model,
        base_url: form.base_url,
      };
      if (form.api_key) payload.api_key = form.api_key;

      const res = await api.put("/settings/ai", payload);
      setSettings(res.data);
      setForm((f) => ({ ...f, api_key: "" }));
      loadModels();
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
      const res = await api.post("/settings/ai/test", {});
      setTestResult(res.data);
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
          <h1 className="text-2xl font-bold">Proveedor de Inteligencia Artificial</h1>
          <p className="text-sm text-muted-foreground mt-1">Conecta un proveedor de IA compatible con OpenAI para activar el agente.</p>
        </div>
        {settings?.is_connected ? (
          <Badge variant="default" className="flex items-center gap-1"><CheckCircle className="h-3 w-3" /> Conectado</Badge>
        ) : (
          <Badge variant="secondary" className="flex items-center gap-1"><AlertCircle className="h-3 w-3" /> Sin conectar</Badge>
        )}
      </div>

      {error && (
        <div className="rounded-md border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
          {error}
        </div>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Proveedor y modelo</CardTitle>
          <CardDescription>OpenAI, Anthropic, o cualquier proveedor compatible con la API de OpenAI.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <label htmlFor="provider" className="text-sm font-medium text-foreground">Proveedor</label>
            <select
              id="provider"
              className="h-9 w-full rounded-md border border-border bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
              value={form.provider}
              onChange={(e) => setForm((f) => ({ ...f, provider: e.target.value, model: "" }))}
            >
              <option value="openai">OpenAI</option>
              <option value="anthropic">Anthropic (Claude)</option>
              <option value="openai_compat">Compatible OpenAI (custom)</option>
            </select>
          </div>

          <div className="space-y-2">
            <label htmlFor="model" className="text-sm font-medium text-foreground">Modelo</label>
            {models.length > 0 ? (
              <select
                id="model"
                className="h-9 w-full rounded-md border border-border bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
                value={form.model}
                onChange={(e) => setForm((f) => ({ ...f, model: e.target.value }))}
              >
                <option value="">Selecciona un modelo</option>
                {models.map((m) => <option key={m} value={m}>{m}</option>)}
              </select>
            ) : (
              <Input id="model" placeholder="ej. gpt-4o-mini" value={form.model} onChange={(e) => setForm((f) => ({ ...f, model: e.target.value }))} />
            )}
          </div>

          <div className="space-y-2">
            <label htmlFor="api_key" className="text-sm font-medium text-foreground">
              API Key {settings?.has_api_key && <span className="text-green-600 text-xs">(guardada)</span>}
            </label>
            <Input id="api_key" type="password" placeholder={settings?.has_api_key ? "••••••••• (dejar en blanco para conservar)" : "sk-..."} value={form.api_key} onChange={(e) => setForm((f) => ({ ...f, api_key: e.target.value }))} />
          </div>

          {form.provider === "openai_compat" && (
            <div className="space-y-2">
              <label htmlFor="base_url" className="text-sm font-medium text-foreground">Base URL del proveedor</label>
              <Input id="base_url" placeholder="https://api.mi-proveedor.com/v1" value={form.base_url} onChange={(e) => setForm((f) => ({ ...f, base_url: e.target.value }))} />
            </div>
          )}
        </CardContent>
      </Card>

      {testResult && (
        <div className={`rounded-md border px-4 py-3 text-sm ${testResult.status.includes("ok") ? "border-green-300 bg-green-50 text-green-800" : "border-destructive/30 bg-destructive/10 text-destructive"}`}>
          <strong>{testResult.status}</strong>
          {testResult.latency && ` — latencia: ${testResult.latency}ms`}
          {testResult.message && ` — ${testResult.message}`}
        </div>
      )}

      {settings?.last_tested_at && (
        <p className="text-xs text-muted-foreground">Última prueba: {new Date(settings.last_tested_at).toLocaleString()}</p>
      )}

      <div className="flex gap-3">
        <Button onClick={handleSave} disabled={saving}>{saving ? "Guardando…" : "Guardar configuración"}</Button>
        <Button variant="outline" onClick={handleTest} disabled={testing}>
          <TestTube className="h-4 w-4 mr-2" />
          {testing ? "Probando…" : "Probar conexión"}
        </Button>
      </div>
    </div>
  );
}
