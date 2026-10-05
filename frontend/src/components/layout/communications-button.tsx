"use client";

import * as React from "react";
import { MessageSquare, Plus, Users, AlertCircle, ChevronRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { api } from "@/lib/api";
import { cn } from "@/lib/utils";

type AudienceSource = {
  key: string;
  name: string;
  ready: boolean;
  message: string | null;
};

type Campaign = {
  id: number;
  name: string;
  type: string;
  status: string;
  audience_source: string;
  created_at: string;
};

type NewCampaign = {
  name: string;
  type: string;
  audience_source: string;
  message_subject: string;
  message_body: string;
};

const STATUS_COLOR: Record<string, string> = {
  draft:     "bg-muted text-muted-foreground",
  scheduled: "bg-primary/10 text-primary",
  sent:      "bg-success/10 text-success",
  cancelled: "bg-destructive/10 text-destructive",
};

const STATUS_LABEL: Record<string, string> = {
  draft: "Borrador", scheduled: "Programada", sent: "Enviada", cancelled: "Cancelada",
};

const TYPES = [
  { key: "mass",         label: "Comunicado masivo" },
  { key: "announcement", label: "Anuncio" },
  { key: "reminder",     label: "Recordatorio" },
  { key: "individual",   label: "Mensaje individual" },
  { key: "campaign",     label: "Campaña" },
];

export function CommunicationsButton() {
  const [open, setOpen] = React.useState(false);
  const [view, setView] = React.useState<"list" | "create">("list");
  const [campaigns, setCampaigns] = React.useState<Campaign[]>([]);
  const [sources, setSources] = React.useState<AudienceSource[]>([]);
  const [loadingCampaigns, setLoadingCampaigns] = React.useState(false);
  const [preview, setPreview] = React.useState<{ count: number; sample: { name: string; email: string | null }[] } | null>(null);
  const [previewLoading, setPreviewLoading] = React.useState(false);
  const [saving, setSaving] = React.useState(false);
  const [error, setError] = React.useState("");
  const [form, setForm] = React.useState<NewCampaign>({
    name: "", type: "mass", audience_source: "", message_subject: "", message_body: "",
  });
  const ref = React.useRef<HTMLDivElement>(null);

  React.useEffect(() => {
    function handle(e: MouseEvent) {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
    }
    document.addEventListener("mousedown", handle);
    return () => document.removeEventListener("mousedown", handle);
  }, []);

  React.useEffect(() => {
    if (!open) return;
    void loadCampaigns();
    void loadSources();
  }, [open]);

  async function loadCampaigns() {
    setLoadingCampaigns(true);
    try {
      const res = await api.get<{ data: Campaign[] }>("/campaigns");
      setCampaigns(res.data.data);
    } catch {
      // non-critical
    } finally {
      setLoadingCampaigns(false);
    }
  }

  async function loadSources() {
    try {
      const res = await api.get<AudienceSource[]>("/campaigns/sources");
      setSources(res.data);
    } catch {
      // non-critical
    }
  }

  async function previewAudience(campaignId?: number) {
    if (!campaignId) return;
    setPreviewLoading(true);
    setPreview(null);
    try {
      const res = await api.get<{ ready: boolean; count?: number; sample?: { name: string; email: string | null }[]; message?: string }>(
        `/campaigns/${campaignId}/preview-audience`,
      );
      if (res.data.ready && res.data.count !== undefined) {
        setPreview({ count: res.data.count, sample: res.data.sample ?? [] });
      }
    } catch {
      // non-critical
    } finally {
      setPreviewLoading(false);
    }
  }

  async function createCampaign(e: React.FormEvent) {
    e.preventDefault();
    if (!form.name || !form.audience_source) {
      setError("Nombre y fuente de destinatarios son obligatorios.");
      return;
    }
    setSaving(true);
    setError("");
    try {
      const res = await api.post<Campaign>("/campaigns", form);
      setCampaigns((p) => [res.data, ...p]);
      setView("list");
      setForm({ name: "", type: "mass", audience_source: "", message_subject: "", message_body: "" });
      setPreview(null);
      void previewAudience(res.data.id);
    } catch {
      setError("No se pudo guardar la campaña. Verifica los datos.");
    } finally {
      setSaving(false);
    }
  }

  function field<K extends keyof NewCampaign>(key: K, val: NewCampaign[K]) {
    setForm((p) => ({ ...p, [key]: val }));
    if (key === "audience_source") setPreview(null);
  }

  const selectedSource = sources.find((s) => s.key === form.audience_source);

  return (
    <div ref={ref} className="relative">
      <Button
        variant="outline"
        size="icon"
        aria-label="Centro de comunicaciones"
        title="Comunicaciones"
        onClick={() => { setOpen((p) => !p); setView("list"); }}
      >
        <MessageSquare className="h-4 w-4" />
      </Button>

      {open && (
        <div className="absolute right-0 top-full z-50 mt-2 w-80 overflow-hidden rounded-xl border border-border bg-card shadow-lg sm:w-[420px]">
          {/* Header */}
          <div className="flex items-center justify-between border-b border-border px-4 py-3">
            <p className="text-sm font-semibold text-foreground">
              {view === "list" ? "Comunicaciones" : "Nueva campaña"}
            </p>
            <div className="flex items-center gap-2">
              {view === "list" && (
                <Button
                  size="sm"
                  variant="outline"
                  className="h-7 gap-1 px-2 text-xs"
                  onClick={() => { setView("create"); setError(""); }}
                >
                  <Plus className="h-3 w-3" /> Nueva
                </Button>
              )}
              {view === "create" && (
                <button
                  type="button"
                  onClick={() => setView("list")}
                  className="text-xs text-muted-foreground hover:text-foreground"
                >
                  ← Volver
                </button>
              )}
            </div>
          </div>

          {/* List view */}
          {view === "list" && (
            <div className="max-h-[440px] overflow-y-auto">
              {loadingCampaigns ? (
                <div className="px-4 py-6 text-center text-sm text-muted-foreground">Cargando...</div>
              ) : campaigns.length === 0 ? (
                <div className="px-4 py-8 text-center">
                  <MessageSquare className="mx-auto h-8 w-8 text-muted-foreground/40" />
                  <p className="mt-2 text-sm font-medium">Sin campañas</p>
                  <p className="mt-1 text-xs text-muted-foreground">Crea la primera campaña.</p>
                </div>
              ) : (
                <ul className="divide-y divide-border">
                  {campaigns.map((c) => (
                    <li key={c.id} className="flex items-center gap-3 px-4 py-3">
                      <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-medium text-foreground">{c.name}</p>
                        <p className="text-xs text-muted-foreground">{c.audience_source.replace(/_/g, " ")}</p>
                      </div>
                      <span className={cn("rounded-full px-2 py-0.5 text-[10px] font-semibold", STATUS_COLOR[c.status] ?? "")}>
                        {STATUS_LABEL[c.status] ?? c.status}
                      </span>
                      <ChevronRight className="h-3.5 w-3.5 shrink-0 text-muted-foreground/40" />
                    </li>
                  ))}
                </ul>
              )}

              {/* Sources legend */}
              {sources.length > 0 && (
                <div className="border-t border-border px-4 py-3">
                  <p className="mb-2 text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">Fuentes disponibles</p>
                  <div className="space-y-1">
                    {sources.map((s) => (
                      <div key={s.key} className="flex items-center gap-2">
                        <span className={cn("h-1.5 w-1.5 rounded-full", s.ready ? "bg-green-500" : "bg-muted-foreground/40")} />
                        <span className="text-xs text-foreground">{s.name}</span>
                        {!s.ready && <span className="text-[10px] text-muted-foreground">— {s.message}</span>}
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Create view */}
          {view === "create" && (
            <form onSubmit={createCampaign} className="max-h-[520px] overflow-y-auto">
              <div className="space-y-4 px-4 py-4">
                <div>
                  <label className="mb-1 block text-xs font-medium text-foreground">Nombre *</label>
                  <Input
                    placeholder="Nombre de la campaña"
                    value={form.name}
                    onChange={(e) => field("name", e.target.value)}
                  />
                </div>

                <div>
                  <label className="mb-1 block text-xs font-medium text-foreground">Tipo</label>
                  <select
                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    value={form.type}
                    onChange={(e) => field("type", e.target.value)}
                  >
                    {TYPES.map((t) => <option key={t.key} value={t.key}>{t.label}</option>)}
                  </select>
                </div>

                <div>
                  <label className="mb-1 block text-xs font-medium text-foreground">Fuente de destinatarios *</label>
                  <div className="space-y-1.5">
                    {sources.map((s) => (
                      <button
                        key={s.key}
                        type="button"
                        disabled={!s.ready}
                        onClick={() => s.ready && field("audience_source", s.key)}
                        className={cn(
                          "flex w-full items-center gap-2 rounded-lg border px-3 py-2.5 text-left text-sm transition-all",
                          !s.ready && "cursor-not-allowed opacity-50",
                          form.audience_source === s.key
                            ? "border-primary bg-primary/10"
                            : "border-border hover:border-primary/40 hover:bg-accent",
                        )}
                      >
                        <Users className="h-4 w-4 shrink-0 text-muted-foreground" />
                        <div className="min-w-0 flex-1">
                          <p className={cn("font-medium", form.audience_source === s.key ? "text-primary" : "text-foreground")}>
                            {s.name}
                          </p>
                          {!s.ready && (
                            <p className="mt-0.5 flex items-center gap-1 text-[11px] text-muted-foreground">
                              <AlertCircle className="h-3 w-3" /> {s.message}
                            </p>
                          )}
                        </div>
                        {form.audience_source === s.key && (
                          <span className="text-[10px] font-semibold text-primary">✓</span>
                        )}
                      </button>
                    ))}
                  </div>
                </div>

                {form.audience_source && selectedSource?.ready && (
                  <div className="rounded-lg bg-muted/50 px-3 py-2">
                    {previewLoading ? (
                      <p className="text-xs text-muted-foreground">Calculando destinatarios...</p>
                    ) : preview ? (
                      <>
                        <p className="text-xs font-semibold text-foreground">{preview.count} destinatarios</p>
                        {preview.sample.length > 0 && (
                          <p className="mt-0.5 text-[11px] text-muted-foreground">
                            Ej: {preview.sample.map((s) => s.name).join(", ")}
                            {preview.count > preview.sample.length ? "..." : ""}
                          </p>
                        )}
                      </>
                    ) : (
                      <button
                        type="button"
                        onClick={() => setPreviewLoading(true)}
                        className="text-xs text-primary hover:underline"
                      >
                        Ver preview de destinatarios
                      </button>
                    )}
                  </div>
                )}

                <div>
                  <label className="mb-1 block text-xs font-medium text-foreground">Asunto</label>
                  <Input
                    placeholder="Asunto del mensaje (opcional)"
                    value={form.message_subject}
                    onChange={(e) => field("message_subject", e.target.value)}
                  />
                </div>

                <div>
                  <label className="mb-1 block text-xs font-medium text-foreground">Mensaje</label>
                  <textarea
                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground"
                    rows={3}
                    placeholder="Contenido del mensaje (opcional)"
                    value={form.message_body}
                    onChange={(e) => field("message_body", e.target.value)}
                  />
                </div>

                {error && (
                  <p className="flex items-center gap-1.5 rounded-lg bg-destructive/10 px-3 py-2 text-xs text-destructive">
                    <AlertCircle className="h-3.5 w-3.5" /> {error}
                  </p>
                )}

                <div className="rounded-lg border border-warning/30 bg-warning/5 px-3 py-2">
                  <p className="text-[11px] text-warning">
                    <strong>Envío:</strong> La campaña se guardará como borrador. El envío real requiere un proveedor de mensajería configurado (SMTP/WhatsApp). Contacta al administrador del sistema.
                  </p>
                </div>
              </div>

              <div className="border-t border-border px-4 py-3">
                <Button type="submit" className="w-full" disabled={saving}>
                  {saving ? "Guardando..." : "Guardar campaña"}
                </Button>
              </div>
            </form>
          )}
        </div>
      )}
    </div>
  );
}
