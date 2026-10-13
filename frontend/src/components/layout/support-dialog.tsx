"use client";

import * as React from "react";
import { HelpCircle, MessageSquarePlus, MessageCircle, FileText, Download, ExternalLink } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { openFeedbackForm } from "@/lib/feedback";

const WHATSAPP_SUPPORT_URL = "https://wa.me/573027029498";

export function SupportDialog() {
  const [showManual, setShowManual] = React.useState(false);

  return (
    <Dialog onOpenChange={() => setShowManual(false)}>
      <DialogTrigger asChild>
        <Button
          variant="outline"
          size="icon"
          aria-label="Centro de soporte"
          title="Soporte y ayuda"
        >
          <HelpCircle className="h-4 w-4" />
        </Button>
      </DialogTrigger>
      <DialogContent className={showManual ? "max-w-4xl" : "max-w-md"}>
        <DialogHeader>
          <DialogTitle>Centro de soporte</DialogTitle>
        </DialogHeader>

        {!showManual ? (
          <div className="space-y-2 pt-1">
            {/* Enviar comentario */}
            <button
              type="button"
              onClick={() => { openFeedbackForm(); }}
              className="flex w-full items-center gap-3 rounded-lg border border-border bg-card px-4 py-3.5 text-left transition-colors hover:bg-muted"
            >
              <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10">
                <MessageSquarePlus className="h-4 w-4 text-primary" />
              </span>
              <div>
                <p className="text-sm font-medium text-foreground">Enviar comentario o sugerencia</p>
                <p className="text-xs text-muted-foreground">Reporta un problema o comparte una mejora</p>
              </div>
              <ExternalLink className="ml-auto h-3.5 w-3.5 shrink-0 text-muted-foreground/50" />
            </button>

            {/* WhatsApp soporte */}
            <a
              href={WHATSAPP_SUPPORT_URL}
              target="_blank"
              rel="noopener noreferrer"
              className="flex w-full items-center gap-3 rounded-lg border border-border bg-card px-4 py-3.5 text-left transition-colors hover:bg-muted"
            >
              <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-500/10">
                <MessageCircle className="h-4 w-4 text-green-600 dark:text-green-400" />
              </span>
              <div>
                <p className="text-sm font-medium text-foreground">Contactar soporte por WhatsApp</p>
                <p className="text-xs text-muted-foreground">Atención técnica en horario laboral</p>
              </div>
              <ExternalLink className="ml-auto h-3.5 w-3.5 shrink-0 text-muted-foreground/50" />
            </a>

            {/* Manual */}
            <div className="rounded-lg border border-border bg-card px-4 py-3.5">
              <div className="flex items-center gap-3">
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10">
                  <FileText className="h-4 w-4 text-primary" />
                </span>
                <div className="min-w-0 flex-1">
                  <p className="text-sm font-medium text-foreground">Manual de usuario</p>
                  <p className="text-xs text-muted-foreground">Guía completa de FidelOS · Demo</p>
                </div>
              </div>
              <div className="mt-3 flex gap-2">
                <Button size="sm" variant="outline" className="flex-1 gap-1.5" onClick={() => setShowManual(true)}>
                  <FileText className="h-3.5 w-3.5" /> Ver manual
                </Button>
                <a href="/manual-demo.pdf" download="manual-fidelos.pdf">
                  <Button size="sm" variant="outline" className="gap-1.5">
                    <Download className="h-3.5 w-3.5" /> Descargar
                  </Button>
                </a>
              </div>
            </div>
          </div>
        ) : (
          <div className="flex flex-col gap-3 pt-1">
            <div className="flex items-center justify-between">
              <p className="text-sm text-muted-foreground">Manual de usuario · Demo</p>
              <div className="flex gap-2">
                <a href="/manual-demo.pdf" download="manual-fidelos.pdf">
                  <Button size="sm" variant="outline" className="gap-1.5">
                    <Download className="h-3.5 w-3.5" /> Descargar PDF
                  </Button>
                </a>
                <Button size="sm" variant="ghost" onClick={() => setShowManual(false)} className="text-xs text-muted-foreground">
                  ← Volver
                </Button>
              </div>
            </div>
            {/* PDF viewer — funciona en Chrome/Edge/Firefox; Safari mobile abre en nueva pestaña */}
            <iframe
              src="/manual-demo.pdf"
              className="h-[65vh] w-full rounded-lg border border-border"
              title="Manual de usuario FidelOS"
            />
            <p className="text-center text-[11px] text-muted-foreground">
              Si el manual no carga,{" "}
              <a href="/manual-demo.pdf" target="_blank" rel="noopener noreferrer" className="text-primary underline underline-offset-2">
                ábrelo en una nueva pestaña
              </a>
              .
            </p>
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}
